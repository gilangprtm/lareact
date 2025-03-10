<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

class GenerateRequestTraitFromDto extends Command
{
    protected $signature = 'make:request-trait {dto : DTO class name without namespace}
                           {--force : Overwrite existing file}';

    protected $description = 'Generate a validation rules trait from a DTO';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dtoName = $this->argument('dto');
        $force = $this->option('force');

        // Construct full DTO class name
        $dtoClass = "App\\DTO\\{$dtoName}";

        // Check if DTO exists
        if (!class_exists($dtoClass)) {
            $this->error("DTO class {$dtoClass} does not exist.");
            return 1;
        }

        // Get entity name (remove 'Dto' or 'RequestDto' from the end)
        $entityName = Str::replaceLast('RequestDto', '', $dtoName);
        $entityName = Str::replaceLast('Dto', '', $entityName);

        // Create the trait name
        $traitName = "{$entityName}Rules";

        // Create directory if not exists
        $directory = app_path('Http/Requests/Traits');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Create the trait file path
        $traitPath = "{$directory}/{$traitName}.php";

        // Check if file already exists
        if (File::exists($traitPath) && !$force) {
            if (!$this->confirm("The trait {$traitName} already exists. Do you want to overwrite it?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Generate trait content
        $content = $this->generateTraitContent($dtoClass, $traitName, $entityName);

        // Write file
        File::put($traitPath, $content);
        $this->info("Request trait {$traitName} generated successfully at {$traitPath}");

        return 0;
    }

    /**
     * Generate the trait content
     */
    protected function generateTraitContent($dtoClass, $traitName, $entityName)
    {
        // Extract validation rules from DTO
        $rules = [];

        if (method_exists($dtoClass, 'rules')) {
            $rules = $dtoClass::rules();
        } else {
            $this->warn("DTO class does not have a rules() method. Generating traits from properties instead.");

            // Get properties and guess rules
            $reflection = new ReflectionClass($dtoClass);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $name = $property->getName();
                if ($name === 'id' || $name === 'created_at' || $name === 'updated_at') {
                    continue;
                }

                $type = 'string'; // Default

                // Try to get the type from the property
                if (PHP_VERSION_ID >= 70400) { // PHP 7.4+
                    $propertyType = $property->getType();
                    if ($propertyType) {
                        $typeName = $propertyType->getName();

                        switch ($typeName) {
                            case 'int':
                                $type = 'integer';
                                break;
                            case 'float':
                                $type = 'numeric';
                                break;
                            case 'bool':
                                $type = 'boolean';
                                break;
                            case 'array':
                                $type = 'array';
                                break;
                            default:
                                $type = 'string';
                        }
                    }
                }

                // Basic rules based on type
                $rules[$name] = [
                    $property->getType() && !$property->getType()->allowsNull() ? 'required' : 'nullable',
                    $type
                ];

                // Add max length for strings
                if ($type === 'string') {
                    $rules[$name][] = 'max:255';
                }
            }
        }

        // Generate method content for baseRules
        $methodContent = "    protected function baseRules()\n" .
            "    {\n" .
            "        return [\n";

        // For each rule, add to method content
        foreach ($rules as $field => $fieldRules) {
            // Skip file fields, those will be handled separately
            if (strpos($field, '_path') !== false || in_array('file', $fieldRules) || in_array('image', $fieldRules)) {
                continue;
            }

            $methodContent .= "            '{$field}' => [";

            if (is_array($fieldRules)) {
                $methodContent .= "'" . implode("', '", $fieldRules) . "'";
            } else {
                $methodContent .= "'{$fieldRules}'";
            }

            $methodContent .= "],\n";
        }

        $methodContent .= "        ];\n" .
            "    }";

        // Generate trait content
        $content = "<?php\n\n" .
            "namespace App\\Http\\Requests\\Traits;\n\n" .
            "trait {$traitName}\n" .
            "{\n" .
            $methodContent . "\n\n" .
            "    /**\n" .
            "     * Get web-specific rules in addition to the base rules.\n" .
            "     */\n" .
            "    protected function webRules()\n" .
            "    {\n" .
            "        return [\n" .
            "            // Web-specific validation rules for {$entityName}\n" .
            "            // Example: 'image' => ['nullable', 'image', 'max:2048']\n" .
            "        ];\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Get API-specific rules in addition to the base rules.\n" .
            "     */\n" .
            "    protected function apiRules()\n" .
            "    {\n" .
            "        return [\n" .
            "            // API-specific validation rules for {$entityName}\n" .
            "            // Example: 'image_url' => ['nullable', 'url']\n" .
            "        ];\n" .
            "    }\n" .
            "}\n";

        return $content;
    }
}
