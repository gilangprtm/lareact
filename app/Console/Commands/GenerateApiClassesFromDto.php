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
     * Generate FormRequest from DTO
     */
    protected function generateRequest($entityName, $dtoClass, $path, $force)
    {
        $requestDtoClass = str_replace('Dto', 'RequestDto', $dtoClass);
        $requestClassName = "{$entityName}Request";
        $requestDirPath = "{$path}/Requests/API";
        $requestFilePath = "{$requestDirPath}/{$requestClassName}.php";

        // Check if the target directory exists, create if not
        if (!File::exists($requestDirPath)) {
            File::makeDirectory($requestDirPath, 0755, true);
        }

        // Check if the file already exists
        if (File::exists($requestFilePath) && !$force) {
            if (!$this->confirm("Request {$requestClassName} already exists. Overwrite?")) {
                $this->info("Request generation skipped.");
                return;
            }
        }

        // Generate Request content
        $content = $this->generateRequestContent($entityName, $requestClassName, $requestDtoClass);

        // Write to file
        File::put($requestFilePath, $content);

        $this->info("Request {$requestClassName} generated successfully at {$requestFilePath}");
    }

    /**
     * Generate the content for the Request class
     */
    protected function generateRequestContent($entityName, $requestClassName, $requestDtoClass)
    {
        $shortDtoClass = class_basename($requestDtoClass);

        return "<?php

namespace App\\Http\\Requests\\API;

use {$requestDtoClass};
use Illuminate\\Foundation\\Http\\FormRequest;

class {$requestClassName} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \\Illuminate\\Contracts\\Validation\\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Menggunakan rules dari DTO
        return {$shortDtoClass}::rules();
    }
    
    /**
     * Convert validated input to DTO
     */
    public function toDto(): {$shortDtoClass}
    {
        return {$shortDtoClass}::fromSource(\$this->validated());
    }
}
";
    }

    /**
     * Generate Resource from DTO
     */
    protected function generateResource($entityName, $dtoClass, $path, $force)
    {
        $resourceClassName = "{$entityName}Resource";
        $resourceDirPath = "{$path}/Resources/API";
        $resourceFilePath = "{$resourceDirPath}/{$resourceClassName}.php";

        // Check if the target directory exists, create if not
        if (!File::exists($resourceDirPath)) {
            File::makeDirectory($resourceDirPath, 0755, true);
        }

        // Check if the file already exists
        if (File::exists($resourceFilePath) && !$force) {
            if (!$this->confirm("Resource {$resourceClassName} already exists. Overwrite?")) {
                $this->info("Resource generation skipped.");
                return;
            }
        }

        // Generate Resource content
        $content = $this->generateResourceContent($entityName, $resourceClassName, $dtoClass);

        // Write to file
        File::put($resourceFilePath, $content);

        $this->info("Resource {$resourceClassName} generated successfully at {$resourceFilePath}");
    }

    /**
     * Generate the content for the Resource class
     */
    protected function generateResourceContent($entityName, $resourceClassName, $dtoClass)
    {
        $shortDtoClass = class_basename($dtoClass);
        $modelClass = "App\\Models\\{$entityName}";

        return "<?php

namespace App\\Http\\Resources\\API;

use {$dtoClass};
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$resourceClassName} extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        // Menggunakan DTO untuk transformasi dan dokumentasi
        return {$shortDtoClass}::fromModel(\$this->resource)->toArray();
    }
}
";
    }
}
