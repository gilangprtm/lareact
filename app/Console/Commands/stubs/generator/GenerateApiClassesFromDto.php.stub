<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class GenerateApiClassesFromDto extends Command
{
    protected $signature = 'make:api-classes {dto : DTO class name without namespace} 
                          {--path=app/Http : Base path for API classes}
                          {--force : Overwrite existing files}';

    protected $description = 'Generate API Request and Resource classes from a DTO';

    public function handle()
    {
        $dtoName = $this->argument('dto');
        $path = $this->option('path');
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

        // Generate Request
        $this->generateRequest($entityName, $dtoClass, $path, $force);

        // Generate Resource
        $this->generateResource($entityName, $dtoClass, $path, $force);

        return 0;
    }

    /**
     * Generate the API Request class
     */
    protected function generateRequest($entityName, $dtoClass, $path, $force)
    {
        $requestName = "{$entityName}Request";
        $requestDtoClass = "App\\DTO\\{$entityName}RequestDto";
        $namespace = 'App\\Http\\Requests\\API';
        $directory = base_path("{$path}/Requests/API");

        // Check if the trait exists, generate it if not
        $traitPath = app_path("Http/Requests/Traits/{$entityName}Rules.php");
        if (!File::exists($traitPath)) {
            $this->call('make:request-trait', [
                'dto' => "{$entityName}RequestDto",
                '--force' => $force
            ]);
            $this->info("Generated trait for {$requestName}.");
        }

        // Create directory if not exists
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $requestPath = "{$directory}/{$requestName}.php";

        if (File::exists($requestPath) && !$force) {
            if (!$this->confirm("The request {$requestName} already exists. Do you want to overwrite it?")) {
                $this->info("API Request generation cancelled.");
                return;
            }
        }

        // Create request content
        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\DTO\\{$entityName}RequestDto;\n" .
            "use App\\Http\\Requests\\Traits\\{$entityName}Rules;\n" .
            "use Illuminate\\Foundation\\Http\\FormRequest;\n\n" .
            "class {$requestName} extends FormRequest\n" .
            "{\n" .
            "    use {$entityName}Rules;\n\n" .
            "    /**\n" .
            "     * Determine if the user is authorized to make this request.\n" .
            "     *\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function authorize(): bool\n" .
            "    {\n" .
            "        return true;\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Get the validation rules that apply to the request.\n" .
            "     *\n" .
            "     * @return array\n" .
            "     */\n" .
            "    public function rules(): array\n" .
            "    {\n" .
            "        return array_merge(\$this->baseRules(), \$this->apiRules());\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Convert the request to a DTO.\n" .
            "     *\n" .
            "     * @return {$entityName}RequestDto\n" .
            "     */\n" .
            "    public function toDto(): {$entityName}RequestDto\n" .
            "    {\n" .
            "        return {$entityName}RequestDto::fromSource(\$this->validated());\n" .
            "    }\n" .
            "}\n";

        File::put($requestPath, $content);
        $this->info("Request {$requestName} generated successfully at {$requestPath}");
    }

    /**
     * Generate the API Resource class
     */
    protected function generateResource($entityName, $dtoClass, $path, $force)
    {
        $resourceName = "{$entityName}Resource";
        $namespace = 'App\\Http\\Resources\\API';
        $directory = base_path("{$path}/Resources/API");

        // Check if the base resource exists, generate it if not
        $baseResourcePath = app_path("Http/Resources/Base/Base{$entityName}Resource.php");
        if (!File::exists($baseResourcePath)) {
            $this->call('make:base-resource', [
                'dto' => "{$entityName}Dto",
                '--force' => $force
            ]);
            $this->info("Generated base resource for {$resourceName}.");
        }

        // Create directory if not exists
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $resourcePath = "{$directory}/{$resourceName}.php";

        if (File::exists($resourcePath) && !$force) {
            if (!$this->confirm("The resource {$resourceName} already exists. Do you want to overwrite it?")) {
                $this->info("API Resource generation cancelled.");
                return;
            }
        }

        // Create resource content
        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\DTO\\{$entityName}Dto;\n" .
            "use App\\Http\\Resources\\Base\\Base{$entityName}Resource;\n\n" .
            "class {$resourceName} extends Base{$entityName}Resource\n" .
            "{\n" .
            "    /**\n" .
            "     * Transform the resource into an array.\n" .
            "     *\n" .
            "     * @param  \\Illuminate\\Http\\Request  \$request\n" .
            "     * @return array\n" .
            "     */\n" .
            "    public function toArray(\$request): array\n" .
            "    {\n" .
            "        // Option 1: Use the DTO for transformation\n" .
            "        \${$entityName}Dto = {$entityName}Dto::fromModel(\$this->resource);\n\n" .
            "        // Option 2: Use the base attributes with API-specific attributes\n" .
            "        return array_merge(\$this->getBaseAttributes(), \$this->getApiAttributes());\n" .
            "    }\n" .
            "}\n";

        File::put($resourcePath, $content);
        $this->info("Resource {$resourceName} generated successfully at {$resourcePath}");
    }
}
