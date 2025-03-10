<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

class GenerateBaseResourceFromDto extends Command
{
    protected $signature = 'make:base-resource {dto : DTO class name without namespace}
                           {--force : Overwrite existing file}';

    protected $description = 'Generate a base resource class from a DTO';

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

        // Get entity name (remove 'Dto' from the end)
        $entityName = Str::replaceLast('Dto', '', $dtoName);

        // Create the resource name
        $baseResourceName = "Base{$entityName}Resource";

        // Create directory if not exists
        $directory = app_path('Http/Resources/Base');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Create the resource file path
        $resourcePath = "{$directory}/{$baseResourceName}.php";

        // Check if file already exists
        if (File::exists($resourcePath) && !$force) {
            if (!$this->confirm("The base resource {$baseResourceName} already exists. Do you want to overwrite it?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Generate resource content
        $content = $this->generateResourceContent($dtoClass, $baseResourceName, $entityName);

        // Write file
        File::put($resourcePath, $content);
        $this->info("Base resource {$baseResourceName} generated successfully at {$resourcePath}");

        return 0;
    }

    /**
     * Generate the base resource content
     */
    protected function generateResourceContent($dtoClass, $baseResourceName, $entityName)
    {
        // Extract attributes from DTO
        $attributes = [];

        // Get properties from DTO
        $reflection = new ReflectionClass($dtoClass);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();

            // Skip URL fields, they will be handled separately
            if (Str::endsWith($name, '_url')) {
                continue;
            }

            $attributes[] = $name;
        }

        // Generate method content for getBaseAttributes
        $methodContent = "    /**\n" .
            "     * Get the base attributes common to both API and Web resources.\n" .
            "     *\n" .
            "     * @return array\n" .
            "     */\n" .
            "    protected function getBaseAttributes(): array\n" .
            "    {\n" .
            "        return [\n";

        // For each attribute, add to method content
        foreach ($attributes as $attribute) {
            $methodContent .= "            '{$attribute}' => \$this->{$attribute},\n";
        }

        $methodContent .= "        ];\n" .
            "    }";

        // Generate resource content
        $content = "<?php\n\n" .
            "namespace App\\Http\\Resources\\Base;\n\n" .
            "use Illuminate\\Http\\Resources\\Json\\JsonResource;\n\n" .
            "abstract class {$baseResourceName} extends JsonResource\n" .
            "{\n" .
            $methodContent . "\n\n" .
            "    /**\n" .
            "     * Get the web-specific attributes.\n" .
            "     *\n" .
            "     * @return array\n" .
            "     */\n" .
            "    protected function getWebAttributes(): array\n" .
            "    {\n" .
            "        return [\n" .
            "            // Add web-specific attributes here\n" .
            "            // Example: 'edit_url' => route('{$entityName}s.edit', \$this->id),\n" .
            "        ];\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Get the API-specific attributes.\n" .
            "     *\n" .
            "     * @return array\n" .
            "     */\n" .
            "    protected function getApiAttributes(): array\n" .
            "    {\n" .
            "        return [\n" .
            "            // Add API-specific attributes here\n" .
            "            // Example: 'links' => [\n" .
            "            //     'self' => route('api.{$entityName}s.show', \$this->id),\n" .
            "            // ],\n" .
            "        ];\n" .
            "    }\n" .
            "}\n";

        return $content;
    }
}
