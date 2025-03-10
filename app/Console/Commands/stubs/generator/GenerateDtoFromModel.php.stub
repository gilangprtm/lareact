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

        // Define properties
        $properties = [];
        $examples = [
            'id' => 1,
            'string' => 'Example text',
            'int' => 42,
            'bool' => true,
            'float' => 3.14,
            'array' => '{"key": "value"}',
        ];

        // Track file fields untuk menambahkan URL
        $filePathFields = [];

        foreach ($columns as $name => $column) {
            $phpType = $column['phpType'];
            $nullable = $column['nullable'];
            $typeHint = $nullable ? "?{$phpType}" : $phpType;
            $example = $examples[$phpType] ?? 'Example value';
            $format = $column['format'] ? ", format=\"{$column['format']}\"" : '';
            $nullableStr = $nullable ? ", nullable=true" : '';

            // Jika ini adalah id, handle secara khusus
            if ($name === 'id') {
                $properties[] = "    /**\n" .
                    "     * @OA\\Property(type=\"integer\", example=1)\n" .
                    "     */\n" .
                    "    public int \$id;\n";
            } else {
                $properties[] = "    /**\n" .
                    "     * @OA\\Property(type=\"{$phpType}\", example=\"{$example}\"{$format}{$nullableStr})\n" .
                    "     */\n" .
                    "    public {$typeHint} \${$name}" . ($nullable ? " = null" : "") . ";\n";
            }

            // Jika field adalah file path, tambahkan ke tracking
            if ($this->isFilePath($name)) {
                $filePathFields[] = $name;

                // Tambahkan properti URL untuk file
                $urlFieldName = $this->getFileFieldName($name) . '_url';
                $properties[] = "    /**\n" .
                    "     * @OA\\Property(type=\"string\", example=\"http://example.com/storage/{$name}\"{$nullableStr})\n" .
                    "     */\n" .
                    "    public ?string \${$urlFieldName} = null;\n";
            }
        }

        // Add relationship properties
        foreach ($relations as $relation => $type) {
            if ($type === 'BelongsTo') {
                $properties[] = "    /**\n" .
                    "     * @OA\\Property(\n" .
                    "     *     type=\"object\",\n" .
                    "     *     @OA\\Property(property=\"id\", type=\"integer\"),\n" .
                    "     *     @OA\\Property(property=\"name\", type=\"string\")\n" .
                    "     * )\n" .
                    "     */\n" .
                    "    public ?object \${$relation} = null;\n";
            } else {
                $properties[] = "    /**\n" .
                    "     * @OA\\Property(\n" .
                    "     *     type=\"array\",\n" .
                    "     *     @OA\\Items(\n" .
                    "     *         type=\"object\",\n" .
                    "     *         @OA\\Property(property=\"id\", type=\"integer\"),\n" .
                    "     *         @OA\\Property(property=\"name\", type=\"string\")\n" .
                    "     *     )\n" .
                    "     */\n" .
                    "    public ?array \${$relation} = null;\n";
            }
        }

        // Generate fromModel method
        $fromModelMethod = $this->generateFromModelMethod($modelName, $columns, $relations, $filePathFields);

        // Create class content
        $content = "<?php\n\n" .
            "namespace {$namespace};\n" .
            "use {$fullModelName};\n\n" .
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
            $fromModelMethod . "\n" .
            "}\n";

        return $content;
    }

    /**
     * Generate the fromModel method
     */
    protected function generateFromModelMethod($modelName, $columns, $relations, $filePathFields = [])
    {
        $method = "    /**\n" .
            "     * Create a DTO from a {$modelName} model\n" .
            "     *\n" .
            "     * @param {$modelName} \$model The model instance\n" .
            "     * @return self\n" .
            "     */\n" .
            "    public static function fromModel({$modelName} \$model): self\n" .
            "    {\n" .
            "        \$dto = self::fromSource(\$model);\n\n";

        // Handle date fields
        $hasDateFields = false;
        foreach ($columns as $name => $column) {
            if ($column['format'] === 'date' || $column['format'] === 'date-time') {
                if (!$hasDateFields) {
                    $method .= "        // Handle date fields\n";
                    $hasDateFields = true;
                }
                $format = $column['format'] === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s';
                $method .= "        \$dto->{$name} = \$model->{$name} ? \$model->{$name}->format('{$format}') : null;\n";
            }
        }

        // Handle file paths and generate URLs
        if (!empty($filePathFields)) {
            $method .= "\n        // Generate file URLs\n";
            foreach ($filePathFields as $pathField) {
                $urlField = $this->getFileFieldName($pathField) . '_url';
                $method .= "        \$dto->{$urlField} = \$model->{$pathField} ? asset('storage/' . \$model->{$pathField}) : null;\n";
            }
        }

        // Handle relations
        if (!empty($relations)) {
            $method .= "\n        // Handle relationships\n";
            foreach ($relations as $relation => $type) {
                $method .= "        if (\$model->relationLoaded('{$relation}')) {\n";
                if ($type === 'BelongsTo') {
                    $method .= "            \$dto->{$relation} = \$model->{$relation} ? (object) [\n" .
                        "                'id' => \$model->{$relation}->id,\n" .
                        "                'name' => \$model->{$relation}->name\n" .
                        "            ] : null;\n";
                } else {
                    $method .= "            \$dto->{$relation} = \$model->{$relation}->map(function (\$item) {\n" .
                        "                return [\n" .
                        "                    'id' => \$item->id,\n" .
                        "                    'name' => \$item->name\n" .
                        "                ];\n" .
                        "            })->toArray();\n";
                }
                $method .= "        }\n";
            }
        }

        $method .= "        return \$dto;\n" .
            "    }";

        return $method;
    }

    /**
     * Mendeteksi apakah kolom merupakan path file
     */
    protected function isFilePath($columnName)
    {
        return Str::endsWith($columnName, '_path');
    }

    /**
     * Mendapatkan nama field file upload dari nama kolom path
     */
    protected function getFileFieldName($pathColumnName)
    {
        // Mengubah xxx_path menjadi xxx
        return Str::replaceLast('_path', '', $pathColumnName);
    }

    /**
     * Menebak tipe file berdasarkan nama kolom
     */
    protected function guessFileType($columnName)
    {
        if (
            Str::contains($columnName, 'image') ||
            Str::contains($columnName, 'photo') ||
            Str::contains($columnName, 'picture') ||
            Str::contains($columnName, 'logo') ||
            Str::contains($columnName, 'avatar') ||
            Str::contains($columnName, 'thumbnail')
        ) {
            return 'image';
        }

        if (
            Str::contains($columnName, 'document') ||
            Str::contains($columnName, 'doc') ||
            Str::contains($columnName, 'pdf')
        ) {
            return 'document';
        }

        return 'file';
    }

    /**
     * Generates file field documentation and validation rules
     */
    protected function getFileFieldDetails($pathColumnName)
    {
        $fileFieldName = $this->getFileFieldName($pathColumnName);
        $fileType = $this->guessFileType($fileFieldName);

        $property = [];
        $rules = [];

        // Properti untuk OpenAPI
        if ($fileType === 'image') {
            $property = [
                'name' => $fileFieldName,
                'docBlock' => "    /**\n" .
                    "     * @OA\\Property(\n" .
                    "     *     property=\"{$fileFieldName}\",\n" .
                    "     *     type=\"string\",\n" .
                    "     *     format=\"binary\",\n" .
                    "     *     description=\"Image file (JPEG, PNG, or GIF)\",\n" .
                    "     *     nullable=true\n" .
                    "     * )\n" .
                    "     */\n",
                'declaration' => "    public \${$fileFieldName} = null;\n",
            ];

            $rules = [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:2048'
            ];
        } elseif ($fileType === 'document') {
            $property = [
                'name' => $fileFieldName,
                'docBlock' => "    /**\n" .
                    "     * @OA\\Property(\n" .
                    "     *     property=\"{$fileFieldName}\",\n" .
                    "     *     type=\"string\",\n" .
                    "     *     format=\"binary\",\n" .
                    "     *     description=\"Document file (PDF, DOC, DOCX, etc.)\",\n" .
                    "     *     nullable=true\n" .
                    "     * )\n" .
                    "     */\n",
                'declaration' => "    public \${$fileFieldName} = null;\n",
            ];

            $rules = [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,txt,xls,xlsx,csv',
                'max:10240'
            ];
        } else {
            $property = [
                'name' => $fileFieldName,
                'docBlock' => "    /**\n" .
                    "     * @OA\\Property(\n" .
                    "     *     property=\"{$fileFieldName}\",\n" .
                    "     *     type=\"string\",\n" .
                    "     *     format=\"binary\",\n" .
                    "     *     description=\"File upload\",\n" .
                    "     *     nullable=true\n" .
                    "     * )\n" .
                    "     */\n",
                'declaration' => "    public \${$fileFieldName} = null;\n",
            ];

            $rules = [
                'nullable',
                'file',
                'max:10240'
            ];
        }

        return [
            'property' => $property,
            'rules' => $rules
        ];
    }

    /**
     * Generate the Request DTO content
     */
    protected function generateRequestDtoContent($modelName, $requestDtoName, $columns)
    {
        $namespace = 'App\\DTO';

        // Define properties and validation rules
        $properties = [];
        $propertyDeclarations = [];
        $rules = [];
        $examples = [
            'id' => 1,
            'string' => 'Example text',
            'int' => 42,
            'bool' => true,
            'float' => 3.14,
            'array' => '{"key": "value"}',
        ];

        // Track file fields untuk menghindari duplikasi
        $fileFields = [];

        foreach ($columns as $name => $column) {
            // Skip primary key and timestamps for request
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Cek apakah ini adalah file path
            if ($this->isFilePath($name)) {
                $fileFieldName = $this->getFileFieldName($name);

                // Jika sudah ada field file untuk path ini, skip
                if (in_array($fileFieldName, $fileFields)) {
                    continue;
                }

                // Tambahkan ke tracking
                $fileFields[] = $fileFieldName;

                // Dapatkan informasi field file
                $fileDetails = $this->getFileFieldDetails($name);
                $propertyDeclarations[] = $fileDetails['property']['docBlock'] . $fileDetails['property']['declaration'];
                $rules[$fileDetails['property']['name']] = $fileDetails['rules'];

                // Skip properti _path karena diganti dengan field file
                continue;
            }

            $phpType = $column['phpType'];
            $nullable = $column['nullable'];
            $typeHint = $nullable ? "?{$phpType}" : $phpType;
            $example = $examples[$phpType] ?? 'Example text';
            $format = $column['format'] ? ", format=\"{$column['format']}\"" : '';
            $nullableStr = $nullable ? ", nullable=true" : '';

            $propertyDeclarations[] = "    /**\n" .
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
            implode("\n", $propertyDeclarations) . "\n" .
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
