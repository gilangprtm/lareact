<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class GenerateWebControllerFromDb extends Command
{
    protected $signature = 'make:web-controller {model : The model class name}
                          {--inertia : Generate controller for Inertia.js (default)}
                          {--blade : Generate controller for Blade templates instead of Inertia}
                          {--prefix= : Route prefix for resource routes (default is kebab case plural of model)}
                          {--force : Overwrite existing files}';

    protected $description = 'Generate Web Controller from existing DB Controller';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $useInertia = !$this->option('blade');
        $routePrefix = $this->option('prefix');
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

            // Check if DB Controller exists
            $dbControllerName = "{$modelShortName}Controller";
            $dbControllerPath = app_path("Http/Controllers/DB/{$dbControllerName}.php");

            if (!File::exists($dbControllerPath)) {
                $this->warn("DB Controller {$dbControllerName} does not exist at {$dbControllerPath}");
                if ($this->confirm("Do you want to generate DB Controller first?", true)) {
                    $this->call('make:db-controller', ['model' => $modelShortName, '--force' => $force]);
                } else {
                    $this->error("Web Controller requires a DB Controller. Aborting.");
                    return 1;
                }
            }

            // Set route prefix if not provided
            if (empty($routePrefix)) {
                $routePrefix = Str::kebab(Str::plural($modelShortName));
            }

            // Generate Web Controller
            $this->generateWebController($modelShortName, $fullModelName, $dbControllerName, $useInertia, $routePrefix, $force);

            // Generate routes
            $this->displayRoutes($modelShortName, $routePrefix);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error generating Web Controller: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Generate the Web Controller
     */
    protected function generateWebController($modelName, $fullModelName, $dbControllerName, $useInertia, $routePrefix, $force)
    {
        $controllerName = "{$modelName}Controller";
        $directory = app_path('Http/Controllers/Web');

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $controllerPath = "{$directory}/{$controllerName}.php";

        if (File::exists($controllerPath) && !$force) {
            if (!$this->confirm("The Web Controller {$controllerName} already exists. Do you want to overwrite it?")) {
                $this->info("Web Controller generation cancelled.");
                return;
            }
        }

        $content = $this->generateWebControllerContent($modelName, $fullModelName, $dbControllerName, $useInertia, $routePrefix);

        File::put($controllerPath, $content);
        $this->info("Web Controller {$controllerName} generated successfully at {$controllerPath}");
    }

    /**
     * Generate the Web Controller content
     */
    protected function generateWebControllerContent($modelName, $fullModelName, $dbControllerName, $useInertia, $routePrefix)
    {
        $namespace = 'App\\Http\\Controllers\\Web';
        $modelImport = $fullModelName;
        $dbControllerImport = "App\\Http\\Controllers\\DB\\{$dbControllerName}";
        $controllerName = "{$modelName}Controller";
        $viewPrefix = Str::kebab($modelName);
        $modelVariable = Str::camel($modelName);
        $modelsVariable = Str::camel(Str::plural($modelName));

        $imports = [
            "namespace {$namespace};\n\n",
            "use App\\Http\\Controllers\\Controller;\n",
            "use {$dbControllerImport};\n",
            "use {$modelImport};\n",
            "use App\\Http\\Requests\\DB\\{$modelName}Request;\n",
            "use Illuminate\\Http\\Request;\n"
        ];

        if ($useInertia) {
            $imports[] = "use Inertia\\Inertia;\n";
        }

        $methods = [];

        // Index method
        if ($useInertia) {
            $indexMethod = "    /**\n" .
                "     * Display a listing of the resource.\n" .
                "     */\n" .
                "    public function index(Request \$request)\n" .
                "    {\n" .
                "        \${$modelsVariable} = \$this->dbController->index();\n" .
                "        return Inertia::render('{$modelName}/Index', [\n" .
                "            '{$modelsVariable}' => \${$modelsVariable},\n" .
                "        ]);\n" .
                "    }";
        } else {
            $indexMethod = "    /**\n" .
                "     * Display a listing of the resource.\n" .
                "     */\n" .
                "    public function index(Request \$request)\n" .
                "    {\n" .
                "        \${$modelsVariable} = \$this->dbController->index();\n" .
                "        return view('{$routePrefix}.index', compact('{$modelsVariable}'));\n" .
                "    }";
        }
        $methods[] = $indexMethod;

        // Create method
        if ($useInertia) {
            $createMethod = "    /**\n" .
                "     * Show the form for creating a new resource.\n" .
                "     */\n" .
                "    public function create()\n" .
                "    {\n" .
                "        return Inertia::render('{$modelName}/Create');\n" .
                "    }";
        } else {
            $createMethod = "    /**\n" .
                "     * Show the form for creating a new resource.\n" .
                "     */\n" .
                "    public function create()\n" .
                "    {\n" .
                "        return view('{$routePrefix}.create');\n" .
                "    }";
        }
        $methods[] = $createMethod;

        // Store method
        $storeMethod = "    /**\n" .
            "     * Store a newly created resource in storage.\n" .
            "     */\n" .
            "    public function store({$modelName}Request \$request)\n" .
            "    {\n" .
            "        \${$modelVariable} = \$this->dbController->create(\$request->validated());\n" .
            "        return redirect()->route('{$routePrefix}.index')\n" .
            "            ->with('success', '{$modelName} created successfully.');\n" .
            "    }";
        $methods[] = $storeMethod;

        // Show method
        if ($useInertia) {
            $showMethod = "    /**\n" .
                "     * Display the specified resource.\n" .
                "     */\n" .
                "    public function show({$modelName} \${$modelVariable})\n" .
                "    {\n" .
                "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
                "        return Inertia::render('{$modelName}/Show', [\n" .
                "            '{$modelVariable}' => \${$modelVariable},\n" .
                "        ]);\n" .
                "    }";
        } else {
            $showMethod = "    /**\n" .
                "     * Display the specified resource.\n" .
                "     */\n" .
                "    public function show({$modelName} \${$modelVariable})\n" .
                "    {\n" .
                "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
                "        return view('{$routePrefix}.show', compact('{$modelVariable}'));\n" .
                "    }";
        }
        $methods[] = $showMethod;

        // Edit method
        if ($useInertia) {
            $editMethod = "    /**\n" .
                "     * Show the form for editing the specified resource.\n" .
                "     */\n" .
                "    public function edit({$modelName} \${$modelVariable})\n" .
                "    {\n" .
                "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
                "        return Inertia::render('{$modelName}/Edit', [\n" .
                "            '{$modelVariable}' => \${$modelVariable},\n" .
                "        ]);\n" .
                "    }";
        } else {
            $editMethod = "    /**\n" .
                "     * Show the form for editing the specified resource.\n" .
                "     */\n" .
                "    public function edit({$modelName} \${$modelVariable})\n" .
                "    {\n" .
                "        \${$modelVariable} = \$this->dbController->find(\${$modelVariable}->id);\n" .
                "        return view('{$routePrefix}.edit', compact('{$modelVariable}'));\n" .
                "    }";
        }
        $methods[] = $editMethod;

        // Update method
        $updateMethod = "    /**\n" .
            "     * Update the specified resource in storage.\n" .
            "     */\n" .
            "    public function update({$modelName}Request \$request, {$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \$this->dbController->update(\$request->validated(), \${$modelVariable}->id);\n" .
            "        return redirect()->route('{$routePrefix}.index')\n" .
            "            ->with('success', '{$modelName} updated successfully.');\n" .
            "    }";
        $methods[] = $updateMethod;

        // Destroy method
        $destroyMethod = "    /**\n" .
            "     * Remove the specified resource from storage.\n" .
            "     */\n" .
            "    public function destroy({$modelName} \${$modelVariable})\n" .
            "    {\n" .
            "        \$this->dbController->delete(\${$modelVariable}->id);\n" .
            "        return redirect()->route('{$routePrefix}.index')\n" .
            "            ->with('success', '{$modelName} deleted successfully.');\n" .
            "    }";
        $methods[] = $destroyMethod;

        // Construct the full controller content
        $content = "<?php\n\n" .
            implode("", $imports) . "\n" .
            "class {$controllerName} extends Controller\n" .
            "{\n" .
            "    public function __construct(\n" .
            "        protected {$dbControllerName} \$dbController\n" .
            "    ) {}\n\n" .
            implode("\n\n", $methods) . "\n" .
            "}\n";

        return $content;
    }

    /**
     * Display routes for the Web Controller
     */
    protected function displayRoutes($modelName, $routePrefix)
    {
        $controllerName = "{$modelName}Controller";

        $this->info("\nAdd these routes to your routes/web.php file:");
        $this->line("use App\\Http\\Controllers\\Web\\{$controllerName};");
        $this->line("\n// {$modelName} routes");
        $this->line("Route::prefix('{$routePrefix}')->group(function () {");
        $this->line("    Route::get('/', [{$controllerName}::class, 'index'])->name('{$routePrefix}.index');");
        $this->line("    Route::get('/create', [{$controllerName}::class, 'create'])->name('{$routePrefix}.create');");
        $this->line("    Route::post('/', [{$controllerName}::class, 'store'])->name('{$routePrefix}.store');");
        $this->line("    Route::get('/{" . Str::camel($modelName) . "}', [{$controllerName}::class, 'show'])->name('{$routePrefix}.show');");
        $this->line("    Route::get('/{" . Str::camel($modelName) . "}/edit', [{$controllerName}::class, 'edit'])->name('{$routePrefix}.edit');");
        $this->line("    Route::put('/{" . Str::camel($modelName) . "}', [{$controllerName}::class, 'update'])->name('{$routePrefix}.update');");
        $this->line("    Route::delete('/{" . Str::camel($modelName) . "}', [{$controllerName}::class, 'destroy'])->name('{$routePrefix}.destroy');");
        $this->line("});");

        // Simpler alternative using resource route
        $this->line("\n// Or more simply:");
        $this->line("Route::resource('{$routePrefix}', {$controllerName}::class);");
    }
}
