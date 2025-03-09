<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

class GenerateDtoFromModel extends Command
{
    protected $signature = 'make:dto {model : The model class name}
                           {--path=app/DTO : The path where the DTO will be generated}
                           {--force : Overwrite existing files}';

    protected $description = 'Generate a DTO from a Laravel model';

    // Memetakan tipe database ke tipe PHP
    protected $typeMap = [
        'integer' => 'int',
        'int' => 'int',
        'bigint' => 'int',
        'smallint' => 'int',
        'tinyint' => 'int',
        'boolean' => 'bool',
        'bool' => 'bool',
        'float' => 'float',
        'double' => 'float',
        'decimal' => 'float',
        'string' => 'string',
        'varchar' => 'string',
        'char' => 'string',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'json' => 'array',
        'datetime' => 'string',
        'date' => 'string',
        'timestamp' => 'string',
        'time' => 'string',
    ];

    // Format default untuk OpenAPI
    protected $formatMap = [
        'datetime' => 'date-time',
        'timestamp' => 'date-time',
        'date' => 'date',
        'time' => 'time',
        'email' => 'email',
        'url' => 'uri',
        'uuid' => 'uuid',
        'ip' => 'ipv4',
    ];

    public function handle()
    {
        $modelName = $this->argument('model');
        $path = $this->option('path');
        $force = $this->option('force');

        // Ada 3 kemungkinan format nama model:
        // 1. Hanya nama ("Category")
        // 2. Dengan namespace ("App\\Models\\Category")
        // 3. Dengan namespace tapi tanpa App ("Models\\Category")

        // Cek apakah nama model berisi namespace
        if (!str_contains($modelName, '\\')) {
            // Jika tidak, coba beberapa path umum secara berurutan
            $candidateClasses = [
                "App\\Models\\{$modelName}",
                "App\\{$modelName}",
            ];

            foreach ($candidateClasses as $candidateClass) {
                if (class_exists($candidateClass)) {
                    $modelName = $candidateClass;
                    break;
                }
            }
        } elseif (!str_starts_with($modelName, 'App\\')) {
            // Jika ada namespace tapi tidak dimulai dengan App, tambahkan
            $modelName = "App\\{$modelName}";
        }

        // Verify model exists
        if (!class_exists($modelName)) {
            $this->error("Model {$modelName} does not exist.");
            $this->info("Pastikan model sudah dibuat dan path benar.");
            $this->info("Format yang diterima:");
            $this->info("- Nama saja: Category");
            $this->info("- Namespace lengkap: App\\Models\\Category");
            return 1;
        }

        $this->info("Menggunakan model: {$modelName}");

        // Get model instance for inspection
        try {
            $model = new $modelName();
        } catch (\Exception $e) {
            $this->error("Gagal membuat instance model: " . $e->getMessage());
            return 1;
        }

        $reflection = new ReflectionClass($model);
        $shortName = $reflection->getShortName();
        $dtoName = "{$shortName}Dto";
        $requestDtoName = "{$shortName}RequestDto";

        try {
            $tableName = $model->getTable();
            $this->info("Model: {$shortName}, Table: {$tableName}");
        } catch (\Exception $e) {
            $this->warn("Tidak bisa mendapatkan nama tabel: " . $e->getMessage());
            $this->info("Akan menggunakan properti fillable/guarded sebagai fallback");
            $tableName = Str::snake(Str::pluralStudly($shortName));
        }

        // Create directory if it doesn't exist
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            $this->info("Membuat direktori: {$path}");
        }

        // Generate DTO file
        $dtoPath = "{$path}/{$dtoName}.php";
        if (File::exists($dtoPath) && !$force) {
            if (!$this->confirm("DTO {$dtoName} already exists. Overwrite?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Get model properties through database schema
        $columns = $this->getTableColumns($model);

        // Jika tidak bisa mendapatkan kolom dari database, gunakan fillable/guarded sebagai fallback
        if (empty($columns)) {
            $this->warn("Tidak bisa mendapatkan kolom dari database untuk model {$shortName}. Menggunakan fallback.");
            $columns = $this->getColumnsFromModel($model);

            if (empty($columns)) {
                $this->error("Tidak bisa mendapatkan properti model. Pastikan model memiliki properti fillable atau guarded yang terdefinisi.");
                return 1;
            }
        }

        $this->info("Berhasil mendapatkan " . count($columns) . " kolom dari " .
            (empty($this->getTableColumns($model)) ? "properti model" : "tabel {$tableName}"));

        // Get model relations
        $relations = $this->getModelRelations($model);
        $this->info("Berhasil mendapatkan " . count($relations) . " relasi dari model {$shortName}");

        // Generate DTO class
        $dtoContent = $this->generateDtoContent($shortName, $dtoName, $columns, $relations, $modelName);
        File::put($dtoPath, $dtoContent);

        // Generate Request DTO class
        $requestDtoPath = "{$path}/{$requestDtoName}.php";
        $requestDtoContent = $this->generateRequestDtoContent($shortName, $requestDtoName, $columns);
        File::put($requestDtoPath, $requestDtoContent);

        $this->info("DTO {$dtoName} generated successfully at {$dtoPath}");
        $this->info("Request DTO {$requestDtoName} generated successfully at {$requestDtoPath}");

        return 0;
    }

    /**
     * Get table columns from a model
     */
    protected function getTableColumns($model)
    {
        try {
            $table = $model->getTable();
            $schema = $model->getConnection()->getSchemaBuilder();

            // Dapatkan daftar kolom menggunakan Schema Builder
            $columnNames = $schema->getColumnListing($table);
            $allColumns = [];

            foreach ($columnNames as $column) {
                try {
                    // Dapatkan tipe kolom
                    $type = $schema->getColumnType($table, $column);

                    // Tentukan apakah nullable - kita asumsikan semua kolom nullable kecuali primary key
                    // karena tidak ada cara langsung untuk memeriksa nullable di Laravel 10+
                    $nullable = $column !== 'id';

                    // Set length berdasarkan tipe data
                    $length = null;
                    if (in_array($type, ['string', 'varchar', 'char'])) {
                        // Default length untuk string
                        $length = 255;
                    }

                    // Map tipe database ke tipe PHP
                    $phpType = $this->typeMap[$type] ?? 'string';

                    // Tentukan format untuk OpenAPI jika berlaku
                    $format = null;
                    foreach ($this->formatMap as $key => $value) {
                        if (str_contains($column, $key)) {
                            $format = $value;
                            break;
                        }
                    }

                    $allColumns[$column] = [
                        'name' => $column,
                        'phpType' => $phpType,
                        'dbType' => $type,
                        'length' => $length,
                        'nullable' => $nullable,
                        'default' => null, // Tidak bisa mendapatkan default value dengan mudah
                        'format' => $format,
                    ];
                } catch (\Exception $e) {
                    $this->warn("Tidak bisa mendapatkan informasi untuk kolom {$column}: " . $e->getMessage());
                }
            }

            // Tambahkan metadata tambahan untuk kolom-kolom khusus
            if (isset($allColumns['id'])) {
                $allColumns['id']['nullable'] = false; // ID biasanya tidak nullable
                $allColumns['id']['phpType'] = 'int';
            }

            if (isset($allColumns['created_at'])) {
                $allColumns['created_at']['format'] = 'date-time';
            }

            if (isset($allColumns['updated_at'])) {
                $allColumns['updated_at']['format'] = 'date-time';
            }

            return $allColumns;
        } catch (\Exception $e) {
            $this->error("Error fetching table columns: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get model relationships
     */
    protected function getModelRelations($model)
    {
        // Pendekatan aman: hanya mendeteksi relasi potensial dari nama metode,
        // tanpa mencoba menjalankan metode yang mungkin menyebabkan error

        $relations = [];
        $reflection = new ReflectionClass($model);

        // Pola nama metode relasi yang umum
        $relationMethodPatterns = [
            'has',
            'belongs',
            'morph'
        ];

        foreach ($reflection->getMethods() as $method) {
            // Hanya metode public yang tidak memiliki parameter
            if (!$method->isPublic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            $methodName = $method->getName();

            // Skip metode yang umum bukan relasi
            $skipMethods = [
                '__construct',
                'toArray',
                'jsonSerialize',
                'attributesToArray',
                'save',
                'delete',
                'update',
                'getAttribute',
                'setAttribute',
                'getAttributes',
                'fill',
                'setRelation',
                'getRelation',
                'getTable',
                'getKey',
                'getKeyName'
            ];

            if (in_array($methodName, $skipMethods)) {
                continue;
            }

            // Periksa apakah nama metode kemungkinan relasi
            $isPotentialRelation = false;
            foreach ($relationMethodPatterns as $pattern) {
                if (Str::startsWith($methodName, $pattern)) {
                    $isPotentialRelation = true;
                    break;
                }
            }

            if (!$isPotentialRelation) {
                continue;
            }

            // Coba analisis konten metode
            try {
                if (!$method->getFileName()) {
                    continue; // Lewati jika tidak bisa mendapatkan file
                }

                $methodBody = file_get_contents($method->getFileName());
                if (!$methodBody) {
                    continue; // Lewati jika tidak bisa membaca file
                }

                $methodStartLine = $method->getStartLine() - 1;
                $methodEndLine = $method->getEndLine();
                $methodLines = array_slice(file($method->getFileName()), $methodStartLine, $methodEndLine - $methodStartLine);
                $methodText = implode('', $methodLines);

                // Coba tebak tipe relasi
                $type = 'Relation';
                $isToMany = false;

                if (
                    strpos($methodText, 'hasMany') !== false ||
                    strpos($methodText, 'belongsToMany') !== false ||
                    strpos($methodText, 'morphMany') !== false ||
                    strpos($methodText, 'morphToMany') !== false ||
                    strpos($methodText, 'hasManyThrough') !== false
                ) {
                    $type = 'HasMany';
                    $isToMany = true;
                } elseif (
                    strpos($methodText, 'hasOne') !== false ||
                    strpos($methodText, 'belongsTo') !== false ||
                    strpos($methodText, 'morphTo') !== false ||
                    strpos($methodText, 'morphOne') !== false ||
                    strpos($methodText, 'hasOneThrough') !== false
                ) {
                    $type = 'HasOne';
                    $isToMany = false;
                }

                // Coba tebak model terkait
                $relatedModel = 'Related';

                // Cobalah beberapa strategi untuk menebak nama model terkait

                // Strategi 1: Cari kelas model di argumen
                if (preg_match('/[\'"]([a-zA-Z\\\\]+)[\'"]/', $methodText, $matches)) {
                    $matched = $matches[1];
                    // Jika ada karakter \, ambil bagian terakhir
                    if (strpos($matched, '\\') !== false) {
                        $parts = explode('\\', $matched);
                        $relatedModel = end($parts);
                    } else {
                        $relatedModel = $matched;
                    }
                }
                // Strategi 2: Tebak dari nama metode (camelCase ke PascalCase)
                else {
                    $relatedModel = Str::singular(Str::studly($methodName));
                }

                $relations[$methodName] = [
                    'type' => $type,
                    'related' => $relatedModel,
                    'isToMany' => $isToMany,
                ];
            } catch (\Exception $e) {
                // Lewati jika terjadi error
                $this->warn("Tidak bisa menganalisis metode {$methodName}: " . $e->getMessage());
                continue;
            }
        }

        return $relations;
    }

    /**
     * Generate the DTO content
     */
    protected function generateDtoContent($modelName, $dtoName, $columns, $relations, $fullModelName)
    {
        $namespace = 'App\\DTO';

        // Mempersiapkan import kelas yang diperlukan
        $imports = ["namespace {$namespace};\n"];
        $imports[] = "use {$fullModelName};\n";

        // Tambahkan Str jika diperlukan (untuk slug)
        if (isset($columns['slug']) && isset($columns['name'])) {
            $imports[] = "use Illuminate\\Support\\Str;\n";
        }

        // Jika ada relasi, tambahkan import untuk model-model yang terkait
        $additionalImports = [];
        foreach ($relations as $relation) {
            // Untuk model terkait, asumsikan namespace App\\Models
            $relatedModel = "App\\Models\\{$relation['related']}";
            // Hanya tambahkan import jika model berbeda dan belum diimport
            if ($relatedModel !== $fullModelName && !in_array("use {$relatedModel};\n", $additionalImports)) {
                $additionalImports[] = "use {$relatedModel};\n";
            }
        }

        $imports = array_merge($imports, $additionalImports);

        // Definisikan properti
        $properties = [];
        $examples = [
            'id' => '1',
            'string' => '"Example"',
            'int' => '42',
            'bool' => 'true',
            'float' => '3.14',
            'array' => '["item1", "item2"]',
        ];

        // Kelompokkan properti berdasarkan jenis (timestamp, regular, relations)
        $timestampProps = [];
        $regularProps = [];
        $idProp = null;

        foreach ($columns as $name => $column) {
            $phpType = $column['phpType'];
            $nullable = $column['nullable'];
            $typeHint = $nullable ? "?{$phpType}" : $phpType;
            $example = $examples[$phpType] ?? 'null';
            $format = $column['format'] ? ", format=\"{$column['format']}\"" : '';
            $nullableStr = $nullable ? ", nullable=true" : '';

            $propStr = "    /**\n" .
                "     * @OA\\Property(type=\"{$phpType}\", example={$example}{$format}{$nullableStr})\n" .
                "     */\n" .
                "    public {$typeHint} \${$name}" . ($nullable ? " = null" : "") . ";\n";

            // Kelompokkan berdasarkan jenis untuk mengatur tata letak yang lebih baik
            if ($name === 'id') {
                $idProp = $propStr;
            } elseif (in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
                $timestampProps[] = $propStr;
            } else {
                $regularProps[] = $propStr;
            }
        }

        // Gabungkan properti dalam urutan yang tepat: id, regular, timestamps
        if ($idProp) {
            $properties[] = $idProp;
        }
        $properties = array_merge($properties, $regularProps, $timestampProps);

        // Tambahkan properti relasi
        $relationProps = [];
        foreach ($relations as $name => $relation) {
            $relatedType = $relation['isToMany'] ? "array" : "object";
            $nullableStr = ", nullable=true";

            $relationProps[] = "    /**\n" .
                "     * @OA\\Property(type=\"{$relatedType}\"{$nullableStr}, description=\"{$relation['related']} {$relation['type']}\")\n" .
                "     */\n" .
                "    public ?{$relatedType} \${$name} = " . ($relation['isToMany'] ? "[]" : "null") . ";\n";
        }

        // Tambahkan properti relasi setelah properti reguler
        $properties = array_merge($properties, $relationProps);

        // Buat metode fromModel
        $fromModelMethod = $this->generateFromModelMethod($modelName, $columns, $relations);

        // Buat konten kelas
        $content = "<?php\n\n" .
            implode("", $imports) . "\n" .
            "/**\n" .
            " * @OA\\Schema(\n" .
            " *     schema=\"{$modelName}\",\n" .
            " *     title=\"{$modelName} DTO\",\n" .
            " *     description=\"Data Transfer Object for {$modelName}\"\n" .
            " * )\n" .
            " */\n" .
            "class {$dtoName} extends BaseDto\n" .
            "{\n" .
            implode("\n", $properties) . "\n" .
            $fromModelMethod .
            "}\n";

        return $content;
    }

    /**
     * Generate the 'fromModel' method
     */
    protected function generateFromModelMethod($modelName, $columns, $relations)
    {
        $body = "        \$dto = self::fromSource(\$model);\n";

        // Add special handling for dates
        $dateFields = [];
        foreach ($columns as $name => $column) {
            if (isset($column['format']) && in_array($column['format'], ['date', 'date-time'])) {
                $dateFields[] = $name;
            }
        }

        if (!empty($dateFields)) {
            $body .= "\n        // Handle date fields\n";
            foreach ($dateFields as $field) {
                $body .= "        \$dto->{$field} = \$model->{$field} ? \$model->{$field}->format('Y-m-d" .
                    (str_contains($columns[$field]['format'], 'time') ? " H:i:s" : "") . "') : null;\n";
            }
        }

        // Add special handling for relations
        if (!empty($relations)) {
            $body .= "\n        // Handle relations\n";
            foreach ($relations as $name => $relation) {
                if ($relation['isToMany']) {
                    $body .= "        \$dto->{$name} = \$model->{$name}->isNotEmpty() ? \$model->{$name}->toArray() : [];\n";
                } else {
                    $body .= "        \$dto->{$name} = \$model->{$name} ? \$model->{$name}->toArray() : null;\n";
                }
            }
        }

        // Custom computations based on column names
        $hasSlug = isset($columns['slug']);
        $hasName = isset($columns['name']);

        if ($hasSlug && $hasName) {
            $body .= "\n        // Ensure slug is set\n";
            $body .= "        if (empty(\$dto->slug) && !empty(\$dto->name)) {\n";
            $body .= "            \$dto->slug = \\Illuminate\\Support\\Str::slug(\$dto->name);\n";
            $body .= "        }\n";
        }

        $method = "    /**\n" .
            "     * Create a DTO from a {$modelName} model\n" .
            "     *\n" .
            "     * @param {$modelName} \$model The model instance\n" .
            "     * @return self\n" .
            "     */\n" .
            "    public static function fromModel({$modelName} \$model): self\n" .
            "    {\n" .
            $body .
            "        return \$dto;\n" .
            "    }\n";

        return $method;
    }

    /**
     * Generate the Request DTO content
     */
    protected function generateRequestDtoContent($modelName, $requestDtoName, $columns)
    {
        $namespace = 'App\\DTO';

        // Define properties and validation rules
        $properties = [];
        $rules = [];
        $examples = [
            'id' => 1,
            'string' => 'Example text',
            'int' => 42,
            'bool' => true,
            'float' => 3.14,
            'array' => '{"key": "value"}',
        ];

        foreach ($columns as $name => $column) {
            // Skip primary key and timestamps for request
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $phpType = $column['phpType'];
            $nullable = $column['nullable'];
            $typeHint = $nullable ? "?{$phpType}" : $phpType;
            $example = $examples[$phpType] ?? '';
            $format = $column['format'] ? ", format=\"{$column['format']}\"" : '';
            $nullableStr = $nullable ? ", nullable=true" : '';

            $properties[] = "    /**\n" .
                "     * @OA\\Property(type=\"{$phpType}\", example=\"{$example}\"{$format}{$nullableStr})\n" .
                "     */\n" .
                "    public {$typeHint} \${$name}" . ($nullable ? " = null" : "") . ";\n";

            // Generate validation rules
            $rule = [];
            if (!$nullable) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            switch ($phpType) {
                case 'string':
                    $rule[] = 'string';
                    if ($column['length']) {
                        $rule[] = "max:{$column['length']}";
                    }
                    if ($column['format'] === 'email') {
                        $rule[] = 'email';
                    } elseif ($column['format'] === 'uri') {
                        $rule[] = 'url';
                    } elseif ($column['format'] === 'date') {
                        $rule[] = 'date';
                    } elseif ($column['format'] === 'date-time') {
                        $rule[] = 'date_format:Y-m-d H:i:s';
                    }
                    break;
                case 'int':
                    $rule[] = 'integer';
                    break;
                case 'bool':
                    $rule[] = 'boolean';
                    break;
                case 'float':
                    $rule[] = 'numeric';
                    break;
                case 'array':
                    $rule[] = 'array';
                    break;
            }

            $rules[$name] = $rule;
        }

        // Format rules as PHP code
        $rulesCode = [];
        foreach ($rules as $name => $rule) {
            $ruleStr = implode("', '", $rule);
            $rulesCode[] = "            '{$name}' => ['{$ruleStr}'],";
        }

        // Create class content
        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "/**\n" .
            " * @OA\\Schema(\n" .
            " *     schema=\"{$modelName}Request\",\n" .
            " *     title=\"{$modelName} Request DTO\",\n" .
            " *     description=\"Request body untuk membuat atau mengupdate {$modelName}\"\n" .
            " * )\n" .
            " */\n" .
            "class {$requestDtoName} extends BaseDto\n" .
            "{\n" .
            implode("\n", $properties) . "\n" .
            "    /**\n" .
            "     * Creates validation rules based on the DTO properties\n" .
            "     */\n" .
            "    public static function rules(): array\n" .
            "    {\n" .
            "        return [\n" .
            implode("\n", $rulesCode) . "\n" .
            "        ];\n" .
            "    }\n" .
            "}\n";

        return $content;
    }

    /**
     * Get columns from model's fillable or guarded properties
     */
    protected function getColumnsFromModel($model)
    {
        $columns = [];

        // Coba dapatkan dari fillable
        $fillable = $model->getFillable();
        if (!empty($fillable)) {
            foreach ($fillable as $column) {
                $columns[$column] = [
                    'name' => $column,
                    'phpType' => $this->guessPhpType($column),
                    'dbType' => 'string', // Default type
                    'length' => null,
                    'nullable' => true,
                    'default' => null,
                    'format' => $this->guessFormat($column),
                ];
            }
        }
        // Jika fillable kosong, coba dapatkan dari properti instance
        else {
            $reflection = new ReflectionClass($model);
            $props = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($props as $prop) {
                $name = $prop->getName();
                if ($name !== 'timestamps' && !$prop->isStatic()) {
                    $columns[$name] = [
                        'name' => $name,
                        'phpType' => $this->guessPhpType($name),
                        'dbType' => 'string', // Default type
                        'length' => null,
                        'nullable' => true,
                        'default' => null,
                        'format' => $this->guessFormat($name),
                    ];
                }
            }
        }

        // Tambahkan kolom standar
        if (!isset($columns['id'])) {
            $columns['id'] = [
                'name' => 'id',
                'phpType' => 'int',
                'dbType' => 'integer',
                'length' => null,
                'nullable' => false,
                'default' => null,
                'format' => null,
            ];
        }

        if (!isset($columns['created_at'])) {
            $columns['created_at'] = [
                'name' => 'created_at',
                'phpType' => 'string',
                'dbType' => 'datetime',
                'length' => null,
                'nullable' => true,
                'default' => null,
                'format' => 'date-time',
            ];
        }

        if (!isset($columns['updated_at'])) {
            $columns['updated_at'] = [
                'name' => 'updated_at',
                'phpType' => 'string',
                'dbType' => 'datetime',
                'length' => null,
                'nullable' => true,
                'default' => null,
                'format' => 'date-time',
            ];
        }

        return $columns;
    }

    /**
     * Guess PHP type from column name
     */
    protected function guessPhpType($column)
    {
        if ($column === 'id' || Str::endsWith($column, '_id')) {
            return 'int';
        }

        if (in_array($column, ['active', 'is_active', 'enabled', 'status', 'approved', 'published'])) {
            return 'bool';
        }

        if (in_array($column, ['price', 'amount', 'total', 'balance'])) {
            return 'float';
        }

        if (Str::contains($column, ['_at', 'date', 'time'])) {
            return 'string'; // For dates, we'll use string with format
        }

        return 'string';
    }

    /**
     * Guess format for OpenAPI from column name
     */
    protected function guessFormat($column)
    {
        if (Str::contains($column, ['email'])) {
            return 'email';
        }

        if (Str::contains($column, ['url', 'website', 'link'])) {
            return 'uri';
        }

        if (Str::contains($column, ['_at'])) {
            return 'date-time';
        }

        if (Str::contains($column, ['date'])) {
            return 'date';
        }

        return null;
    }
}
