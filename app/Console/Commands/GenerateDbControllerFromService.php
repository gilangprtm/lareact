<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class GenerateDbControllerFromService extends Command
{
    protected $signature = 'make:db-controller {model : The model class name}
                           {--web : Generate Web Controller too}
                           {--force : Overwrite existing files}';

    protected $description = 'Generate DB Controller, Request, and Resource from a Model and Service';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $generateWeb = $this->option('web');
        $force = $this->option('force');

        // Check if model exists
        try {
            // Handle both formats: "App\Models\User" or just "User"
            $fullModelName = $modelName;
            if (!Str::contains($modelName, '\\')) {
                $fullModelName = "App\\Models\\{$modelName}";
            }

            // Verify the model exists
            if (!class_exists($fullModelName)) {
                $this->error("Model {$fullModelName} does not exist!");
                return 1;
            }

            // Get the class name without namespace
            $reflection = new ReflectionClass($fullModelName);
            $modelShortName = $reflection->getShortName();

            // Create names for the various classes
            $serviceName = "{$modelShortName}Service";
            $serviceInterfaceName = "{$modelShortName}ServiceInterface";
            $dbControllerName = "{$modelShortName}Controller";
            $dbRequestName = "{$modelShortName}Request";
            $dbResourceName = "{$modelShortName}Resource";

            // Check if Service exists
            $serviceInterfacePath = app_path("Services/DB/Contracts/{$serviceInterfaceName}.php");
            $servicePath = app_path("Services/DB/Providers/{$serviceName}.php");

            if (!File::exists($serviceInterfacePath) || !File::exists($servicePath)) {
                $this->warn("Service {$serviceName} or Interface {$serviceInterfaceName} does not exist.");
                if ($this->confirm("Do you want to generate it first?", true)) {
                    $this->call('make:service', ['model' => $modelShortName]);
                } else {
                    return 1;
                }
            }

            // Create DB Controller
            $this->generateDbController($modelShortName, $fullModelName, $serviceName, $serviceInterfaceName, $force);

            // Create DB Request
            $this->generateDbRequest($modelShortName, $fullModelName, $force);

            // Create DB Resource
            $this->generateDbResource($modelShortName, $fullModelName, $force);

            // Create Web Controller if requested
            if ($generateWeb) {
                $this->generateWebController($modelShortName, $fullModelName, $dbControllerName, $force);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error generating controller: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Generate the DB Controller
     */
    protected function generateDbController($modelName, $fullModelName, $serviceName, $serviceInterfaceName, $force)
    {
        $controllerName = "{$modelName}Controller";
        $directory = app_path('Http/Controllers/DB');

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $controllerPath = "{$directory}/{$controllerName}.php";

        if (File::exists($controllerPath) && !$force) {
            if (!$this->confirm("The controller {$controllerName} already exists. Do you want to overwrite it?")) {
                $this->info("DB Controller generation cancelled.");
                return;
            }
        }

        $content = $this->generateDbControllerContent($modelName, $fullModelName, $serviceName, $serviceInterfaceName);

        File::put($controllerPath, $content);
        $this->info("DB Controller {$controllerName} generated successfully at {$controllerPath}");
    }

    /**
     * Generate the DB Request
     */
    protected function generateDbRequest($modelName, $fullModelName, $force)
    {
        $requestName = "{$modelName}Request";
        $directory = app_path('Http/Requests/DB');

        // Check if the trait exists, generate it if not
        $traitPath = app_path("Http/Requests/Traits/{$modelName}Rules.php");
        if (!File::exists($traitPath)) {
            $this->call('make:request-trait', [
                'dto' => "{$modelName}RequestDto",
                '--force' => $force
            ]);
            $this->info("Generated trait for {$requestName}.");
        }

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $requestPath = "{$directory}/{$requestName}.php";

        if (File::exists($requestPath) && !$force) {
            if (!$this->confirm("The request {$requestName} already exists. Do you want to overwrite it?")) {
                $this->info("DB Request generation cancelled.");
                return;
            }
        }

        $content = $this->generateDbRequestContent($modelName, $fullModelName);

        File::put($requestPath, $content);
        $this->info("DB Request {$requestName} generated successfully at {$requestPath}");
    }

    /**
     * Generate the DB Resource
     */
    protected function generateDbResource($modelName, $fullModelName, $force)
    {
        $resourceName = "{$modelName}Resource";
        $directory = app_path('Http/Resources/DB');

        // Check if the base resource exists, generate it if not
        $baseResourcePath = app_path("Http/Resources/Base/Base{$modelName}Resource.php");
        if (!File::exists($baseResourcePath)) {
            $this->call('make:base-resource', [
                'dto' => "{$modelName}Dto",
                '--force' => $force
            ]);
            $this->info("Generated base resource for {$resourceName}.");
        }

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $resourcePath = "{$directory}/{$resourceName}.php";

        if (File::exists($resourcePath) && !$force) {
            if (!$this->confirm("The resource {$resourceName} already exists. Do you want to overwrite it?")) {
                $this->info("DB Resource generation cancelled.");
                return;
            }
        }

        $content = $this->generateDbResourceContent($modelName, $fullModelName);

        File::put($resourcePath, $content);
        $this->info("DB Resource {$resourceName} generated successfully at {$resourcePath}");
    }

    /**
     * Generate the Web Controller
     */
    protected function generateWebController($modelName, $fullModelName, $dbControllerName, $force)
    {
        $controllerName = "{$modelName}Controller";
        $directory = app_path('Http/Controllers/Web');

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $controllerPath = "{$directory}/{$controllerName}.php";

        if (File::exists($controllerPath) && !$force) {
            if (!$this->confirm("The web controller {$controllerName} already exists. Do you want to overwrite it?")) {
                $this->info("Web Controller generation cancelled.");
                return;
            }
        }

        $content = $this->generateWebControllerContent($modelName, $fullModelName, $dbControllerName);

        File::put($controllerPath, $content);
        $this->info("Web Controller {$controllerName} generated successfully at {$controllerPath}");
    }

    /**
     * Generate the DB Controller content
     */
    protected function generateDbControllerContent($modelName, $fullModelName, $serviceName, $serviceInterfaceName)
    {
        $namespace = 'App\\Http\\Controllers\\DB';
        $modelImport = $fullModelName;
        $serviceImport = "App\\Services\\DB\\Providers\\{$serviceName}";
        $serviceInterfaceImport = "App\\Services\\DB\\Contracts\\{$serviceInterfaceName}";
        $controllerName = "{$modelName}Controller";
        $tableVariable = Str::camel(Str::plural($modelName));

        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\Http\\Controllers\\Controller;\n" .
            "use {$modelImport};\n" .
            "use {$serviceInterfaceImport};\n" .
            "use Illuminate\\Database\\Eloquent\\Collection;\n" .
            "use Illuminate\\Pagination\\LengthAwarePaginator;\n\n" .
            "class {$controllerName} extends Controller\n" .
            "{\n" .
            "    public function __construct(\n" .
            "        protected {$serviceInterfaceName} \$" . Str::camel($serviceName) . "\n" .
            "    ) {}\n\n" .
            "    public function index(): LengthAwarePaginator\n" .
            "    {\n" .
            "        return \$this->" . Str::camel($serviceName) . "->getAllWith{$modelName}s();\n" .
            "    }\n\n" .
            "    public function find(int \$id): {$modelName}\n" .
            "    {\n" .
            "        return \$this->" . Str::camel($serviceName) . "->find(\$id);\n" .
            "    }\n\n" .
            "    public function create(array \$data): {$modelName}\n" .
            "    {\n" .
            "        return \$this->" . Str::camel($serviceName) . "->create(\$data);\n" .
            "    }\n\n" .
            "    public function update(array \$data, int \$id): {$modelName}\n" .
            "    {\n" .
            "        \${$modelName} = \$this->" . Str::camel($serviceName) . "->find(\$id);\n" .
            "        return \$this->" . Str::camel($serviceName) . "->update(\$data, \${$modelName});\n" .
            "    }\n\n" .
            "    public function delete(int \$id): bool\n" .
            "    {\n" .
            "        \${$modelName} = \$this->" . Str::camel($serviceName) . "->find(\$id);\n" .
            "        return \$this->" . Str::camel($serviceName) . "->delete(\${$modelName});\n" .
            "    }\n\n" .
            "    public function getAll(): Collection\n" .
            "    {\n" .
            "        return \$this->" . Str::camel($serviceName) . "->getAll();\n" .
            "    }\n" .
            "}\n";

        return $content;
    }

    /**
     * Generate the DB Request content
     */
    protected function generateDbRequestContent($modelName, $fullModelName)
    {
        $namespace = 'App\\Http\\Requests\\DB';
        $requestName = "{$modelName}Request";
        $dtoClass = "App\\DTO\\{$modelName}RequestDto";

        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\DTO\\{$modelName}RequestDto;\n" .
            "use App\\Http\\Requests\\Traits\\{$modelName}Rules;\n" .
            "use Illuminate\\Foundation\\Http\\FormRequest;\n\n" .
            "class {$requestName} extends FormRequest\n" .
            "{\n" .
            "    use {$modelName}Rules;\n\n" .
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
            "        return array_merge(\$this->baseRules(), \$this->webRules());\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Convert the request to a DTO.\n" .
            "     *\n" .
            "     * @return {$modelName}RequestDto\n" .
            "     */\n" .
            "    public function toDto(): {$modelName}RequestDto\n" .
            "    {\n" .
            "        return {$modelName}RequestDto::fromSource(\$this->validated());\n" .
            "    }\n" .
            "}\n";

        return $content;
    }

    /**
     * Generate the DB Resource content
     */
    protected function generateDbResourceContent($modelName, $fullModelName)
    {
        $namespace = 'App\\Http\\Resources\\DB';
        $resourceName = "{$modelName}Resource";
        $dtoClass = "App\\DTO\\{$modelName}Dto";

        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\DTO\\{$modelName}Dto;\n" .
            "use App\\Http\\Resources\\Base\\Base{$modelName}Resource;\n\n" .
            "class {$resourceName} extends Base{$modelName}Resource\n" .
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
            "        \${$modelName}Dto = {$modelName}Dto::fromModel(\$this->resource);\n\n" .
            "        // Option 2: Use the base attributes with web-specific attributes\n" .
            "        return array_merge(\$this->getBaseAttributes(), \$this->getWebAttributes());\n" .
            "    }\n" .
            "}\n";

        return $content;
    }

    /**
     * Generate the Web Controller content
     */
    protected function generateWebControllerContent($modelName, $fullModelName, $dbControllerName)
    {
        $namespace = 'App\\Http\\Controllers\\Web';
        $modelImport = $fullModelName;
        $dbControllerImport = "App\\Http\\Controllers\\DB\\{$dbControllerName}";
        $controllerName = "{$modelName}Controller";
        $viewPrefix = Str::kebab(Str::plural($modelName));
        $modelVariable = Str::camel($modelName);
        $modelsVariable = Str::camel(Str::plural($modelName));

        $content = "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use App\\Http\\Controllers\\Controller;\n" .
            "use {$dbControllerImport};\n" .
            "use {$modelImport};\n" .
            "use App\\Http\\Requests\\DB\\{$modelName}Request;\n" .
            "use Illuminate\\Http\\Request;\n" .
            "use Inertia\\Inertia;\n\n" .
            "class {$controllerName} extends Controller\n" .
            "{\n" .
            "    public function __construct(\n" .
            "        protected {$dbControllerName} \$dbController\n" .
            "    ) {}\n\n" .
            "    /**\n" .
            "     * Display a listing of the resource.\n" .
            "     */\n" .
            "    public function index()\n" .
            "    {\n" .
            "        \${$modelsVariable} = \$this->dbController->index();\n" .
            "        return Inertia::render('{$modelName}/Index', [\n" .
            "            '{$modelsVariable}' => \${$modelsVariable},\n" .
            "        ]);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Show the form for creating a new resource.\n" .
            "     */\n" .
            "    public function create()\n" .
            "    {\n" .
            "        return Inertia::render('{$modelName}/Create');\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Store a newly created resource in storage.\n" .
            "     */\n" .
            "    public function store({$modelName}Request \$request)\n" .
            "    {\n" .
            "        \${$modelVariable} = \$this->dbController->create(\$request->validated());\n" .
            "        return redirect()->route('{$viewPrefix}.index')\n" .
            "            ->with('success', '{$modelName} created successfully.');\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Display the specified resource.\n" .
            "     */\n" .
            "    public function view({$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
            "        return Inertia::render('{$modelName}/Show', [\n" .
            "            '{$modelVariable}' => \${$modelVariable},\n" .
            "        ]);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Show the form for editing the specified resource.\n" .
            "     */\n" .
            "    public function edit({$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
            "        return Inertia::render('{$modelName}/Edit', [\n" .
            "            '{$modelVariable}' => \${$modelVariable},\n" .
            "        ]);\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Update the specified resource in storage.\n" .
            "     */\n" .
            "    public function update({$modelName}Request \$request, {$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \$this->dbController->update(\$request->validated(), \${$modelVariable}->id);\n" .
            "        return redirect()->route('{$viewPrefix}.index')\n" .
            "            ->with('success', '{$modelName} updated successfully.');\n" .
            "    }\n\n" .
            "    /**\n" .
            "     * Remove the specified resource from storage.\n" .
            "     */\n" .
            "    public function destroy({$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \$this->dbController->delete(\${$modelVariable}->id);\n" .
            "        return redirect()->route('{$viewPrefix}.index')\n" .
            "            ->with('success', '{$modelName} deleted successfully.');\n" .
            "    }\n" .
            "}\n";

        return $content;
    }
}
