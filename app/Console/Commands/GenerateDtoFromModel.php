<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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

        // Prepend App\Models if not a fully qualified name and doesn't have a namespace
        if (!str_contains($modelName, '\\')) {
            $modelName = "App\\Models\\{$modelName}";
        }

        // Verify model exists
        if (!class_exists($modelName)) {
            $this->error("Model {$modelName} does not exist.");
            return 1;
        }

        // Get model instance for inspection
        $model = new $modelName();
        $reflection = new ReflectionClass($model);
        $shortName = $reflection->getShortName();
        $dtoName = "{$shortName}Dto";
        $requestDtoName = "{$shortName}RequestDto";
        $tableName = $model->getTable();

        // Create directory if it doesn't exist
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
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
        if (empty($columns)) {
            $this->error("Could not get columns for model {$shortName}.");
            return 1;
        }

        // Get model relations
        $relations = $this->getModelRelations($model);

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
            $connection = $model->getConnection();
            $schema = $connection->getDoctrineSchemaManager();

            // Set the database platform for cross-platform compatibility
            $platform = $schema->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');

            $baseColumns = $schema->listTableColumns($table);
            $columns = [];

            foreach ($baseColumns as $column) {
                $name = $column->getName();
                $type = $column->getType()->getName();
                $length = $column->getLength();
                $nullable = !$column->getNotnull();
                $default = $column->getDefault();

                // Skip columns typically handled differently
                if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                // Map database type to PHP type
                $phpType = $this->typeMap[$type] ?? 'string';

                // Determine format for OpenAPI if applicable
                $format = null;
                foreach ($this->formatMap as $key => $value) {
                    if (str_contains($name, $key)) {
                        $format = $value;
                        break;
                    }
                }

                $columns[$name] = [
                    'name' => $name,
                    'phpType' => $phpType,
                    'dbType' => $type,
                    'length' => $length,
                    'nullable' => $nullable,
                    'default' => $default,
                    'format' => $format,
                ];
            }

            // Add special columns back with proper handling
            $columns['id'] = [
                'name' => 'id',
                'phpType' => 'int',
                'dbType' => 'integer',
                'length' => null,
                'nullable' => true, // Allow null for new records
                'default' => null,
                'format' => null,
            ];

            $columns['created_at'] = [
                'name' => 'created_at',
                'phpType' => 'string',
                'dbType' => 'datetime',
                'length' => null,
                'nullable' => false,
                'default' => null,
                'format' => 'date-time',
            ];

            $columns['updated_at'] = [
                'name' => 'updated_at',
                'phpType' => 'string',
                'dbType' => 'datetime',
                'length' => null,
                'nullable' => false,
                'default' => null,
                'format' => 'date-time',
            ];

            return $columns;
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
        $relations = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods() as $method) {
            // Only public methods that might be relations
            if (!$method->isPublic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                // Try to invoke the method to see if it's a relation
                $return = $method->invoke($model);

                // Check if return value is a relation
                if (
                    $return instanceof \Illuminate\Database\Eloquent\Relations\Relation &&
                    !$method->getDocComment() &&
                    !method_exists(get_parent_class($model), $method->getName())
                ) {
                    $relationType = (new ReflectionClass($return))->getShortName();
                    $relatedModel = (new ReflectionClass($return->getRelated()))->getShortName();

                    $relations[$method->getName()] = [
                        'type' => $relationType,
                        'related' => $relatedModel,
                        'isToMany' => in_array($relationType, ['HasMany', 'BelongsToMany', 'MorphMany', 'MorphToMany', 'HasManyThrough']),
                    ];
                }
            } catch (\Exception $e) {
                // Not a relation or couldn't determine
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
        $imports = ["namespace {$namespace};\n\nuse {$fullModelName};\n"];

        // Define properties
        $properties = [];
        $examples = [
            'id' => 1,
            'string' => 'Example',
            'int' => 42,
            'bool' => true,
            'float' => 3.14,
            'array' => '{"key": "value"}',
        ];

        foreach ($columns as $name => $column) {
            $phpType = $column['phpType'];
            $nullable = $column['nullable'];
            $typeHint = $nullable ? "?{$phpType}" : $phpType;
            $example = $examples[$phpType] ?? '';
            $format = $column['format'] ? ", format=\"{$column['format']}\"" : '';
            $nullableStr = $nullable ? ", nullable=true" : '';

            $properties[] = "    /**\n" .
                "     * @OA\\Property(type=\"{$phpType}\", example={$example}{$format}{$nullableStr})\n" .
                "     */\n" .
                "    public {$typeHint} \${$name}" . ($nullable ? " = null" : "") . ";\n";
        }

        // Add relation properties
        foreach ($relations as $name => $relation) {
            $relatedType = $relation['isToMany'] ? "array" : "object";
            $exampleValue = $relation['isToMany'] ? "[]" : "null";
            $nullableStr = ", nullable=true";

            $properties[] = "    /**\n" .
                "     * @OA\\Property(type=\"{$relatedType}\"{$nullableStr}, description=\"{$relation['related']} {$relation['type']}\")\n" .
                "     */\n" .
                "    public ?{$relatedType} \${$name} = null;\n";
        }

        // Create from model static method
        $fromModelMethod = $this->generateFromModelMethod($modelName, $columns, $relations);

        // Create class content
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

        // Add special handling for relations
        foreach ($relations as $name => $relation) {
            if ($relation['isToMany']) {
                $body .= "        \$dto->{$name} = \$model->{$name}->toArray();\n";
            } else {
                $body .= "        \$dto->{$name} = \$model->{$name} ? \$model->{$name}->toArray() : null;\n";
            }
        }

        // Custom computation examples for specific fields
        if (isset($columns['slug'])) {
            $body .= "        // Ensure slug is set\n";
            $body .= "        if (empty(\$dto->slug) && !empty(\$dto->name)) {\n";
            $body .= "            \$dto->slug = Str::slug(\$dto->name);\n";
            $body .= "        }\n";
        }

        $method = "    /**\n" .
            "     * Create a DTO from a {$modelName} model\n" .
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
}
