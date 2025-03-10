<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class GenerateRepositoryFromModel extends Command
{
    protected $signature = 'make:repository {model : The model class name}
                           {--force : Overwrite existing files}';

    protected $description = 'Generate repository interface and implementation for a model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelName = $this->argument('model');
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

            // Generate repository interface
            $interfaceName = "{$modelShortName}RepositoryInterface";
            $interfaceContent = $this->generateRepositoryInterfaceContent($modelShortName, $fullModelName);
            $interfaceDirectory = app_path('Repositories/Contracts');

            if (!File::isDirectory($interfaceDirectory)) {
                File::makeDirectory($interfaceDirectory, 0755, true);
            }

            $interfacePath = "{$interfaceDirectory}/{$interfaceName}.php";

            if (File::exists($interfacePath) && !$force) {
                if (!$this->confirm("The repository interface {$interfaceName} already exists. Do you want to overwrite it?")) {
                    $this->info("Repository interface generation cancelled.");
                    return 1;
                }
            }

            File::put($interfacePath, $interfaceContent);
            $this->info("Repository interface {$interfaceName} generated successfully at {$interfacePath}");

            // Generate repository implementation
            $implementationName = "{$modelShortName}Repository";
            $implementationContent = $this->generateRepositoryImplementationContent($modelShortName, $fullModelName, $interfaceName);
            $implementationDirectory = app_path('Repositories/Eloquent');

            if (!File::isDirectory($implementationDirectory)) {
                File::makeDirectory($implementationDirectory, 0755, true);
            }

            $implementationPath = "{$implementationDirectory}/{$implementationName}.php";

            if (File::exists($implementationPath) && !$force) {
                if (!$this->confirm("The repository implementation {$implementationName} already exists. Do you want to overwrite it?")) {
                    $this->info("Repository implementation generation cancelled.");
                    return 1;
                }
            }

            File::put($implementationPath, $implementationContent);
            $this->info("Repository implementation {$implementationName} generated successfully at {$implementationPath}");

            // Register repository binding in AppServiceProvider
            $this->registerRepositoryBinding($interfaceName, $implementationName);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error generating repository: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Generate repository interface content
     */
    protected function generateRepositoryInterfaceContent($modelName, $fullModelName)
    {
        $namespace = 'App\\Repositories\\Contracts';
        $interfaceName = "{$modelName}RepositoryInterface";

        return "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use {$fullModelName};\n" .
            "use Illuminate\\Database\\Eloquent\\Collection;\n" .
            "use Illuminate\\Pagination\\LengthAwarePaginator;\n\n" .
            "interface {$interfaceName}\n" .
            "{\n" .
            "    /**\n" .
            "     * Get all records\n" .
            "     *\n" .
            "     * @param array \$columns\n" .
            "     * @return Collection\n" .
            "     */\n" .
            "    public function getAll(array \$columns = ['*']): Collection;\n\n" .

            "    /**\n" .
            "     * Get paginated records\n" .
            "     *\n" .
            "     * @param int \$perPage\n" .
            "     * @param array \$columns\n" .
            "     * @return LengthAwarePaginator\n" .
            "     */\n" .
            "    public function getPaginated(int \$perPage = 15, array \$columns = ['*']): LengthAwarePaginator;\n\n" .

            "    /**\n" .
            "     * Find by ID\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @param array \$columns\n" .
            "     * @return {$modelName}|null\n" .
            "     */\n" .
            "    public function findById(int \$id, array \$columns = ['*']): ?{$modelName};\n\n" .

            "    /**\n" .
            "     * Create new record\n" .
            "     *\n" .
            "     * @param array \$data\n" .
            "     * @return {$modelName}\n" .
            "     */\n" .
            "    public function create(array \$data): {$modelName};\n\n" .

            "    /**\n" .
            "     * Update record\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @param array \$data\n" .
            "     * @return {$modelName}|null\n" .
            "     */\n" .
            "    public function update(int \$id, array \$data): ?{$modelName};\n\n" .

            "    /**\n" .
            "     * Delete record\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function delete(int \$id): bool;\n\n" .

            "    /**\n" .
            "     * Get cached data\n" .
            "     *\n" .
            "     * @param string \$key\n" .
            "     * @param \\Closure \$callback\n" .
            "     * @param int \$ttl Time to live in seconds\n" .
            "     * @return mixed\n" .
            "     */\n" .
            "    public function getCached(string \$key, \\Closure \$callback, int \$ttl = 3600);\n\n" .

            "    /**\n" .
            "     * Clear cache for a specific key or pattern\n" .
            "     *\n" .
            "     * @param string \$key\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function clearCache(string \$key): bool;\n" .
            "}\n";
    }

    /**
     * Generate repository implementation content
     */
    protected function generateRepositoryImplementationContent($modelName, $fullModelName, $interfaceName)
    {
        $namespace = 'App\\Repositories\\Eloquent';
        $implementationName = "{$modelName}Repository";
        $interfaceNamespace = 'App\\Repositories\\Contracts';

        return "<?php\n\n" .
            "namespace {$namespace};\n\n" .
            "use {$fullModelName};\n" .
            "use {$interfaceNamespace}\\{$interfaceName};\n" .
            "use Illuminate\\Database\\Eloquent\\Collection;\n" .
            "use Illuminate\\Pagination\\LengthAwarePaginator;\n" .
            "use Illuminate\\Support\\Facades\\Cache;\n\n" .
            "class {$implementationName} implements {$interfaceName}\n" .
            "{\n" .
            "    /**\n" .
            "     * Cache prefix for this repository\n" .
            "     *\n" .
            "     * @var string\n" .
            "     */\n" .
            "    protected string \$cachePrefix = '{$modelName}_';\n\n" .

            "    /**\n" .
            "     * Get all records\n" .
            "     *\n" .
            "     * @param array \$columns\n" .
            "     * @return Collection\n" .
            "     */\n" .
            "    public function getAll(array \$columns = ['*']): Collection\n" .
            "    {\n" .
            "        return \$this->getCached(\$this->cachePrefix . 'all', function () use (\$columns) {\n" .
            "            return {$modelName}::all(\$columns);\n" .
            "        });\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Get paginated records\n" .
            "     *\n" .
            "     * @param int \$perPage\n" .
            "     * @param array \$columns\n" .
            "     * @return LengthAwarePaginator\n" .
            "     */\n" .
            "    public function getPaginated(int \$perPage = 15, array \$columns = ['*']): LengthAwarePaginator\n" .
            "    {\n" .
            "        // Pagination results are not cached because they depend on the page parameter\n" .
            "        return {$modelName}::paginate(\$perPage, \$columns);\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Find by ID\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @param array \$columns\n" .
            "     * @return {$modelName}|null\n" .
            "     */\n" .
            "    public function findById(int \$id, array \$columns = ['*']): ?{$modelName}\n" .
            "    {\n" .
            "        return \$this->getCached(\$this->cachePrefix . \$id, function () use (\$id, \$columns) {\n" .
            "            return {$modelName}::find(\$id, \$columns);\n" .
            "        });\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Create new record\n" .
            "     *\n" .
            "     * @param array \$data\n" .
            "     * @return {$modelName}\n" .
            "     */\n" .
            "    public function create(array \$data): {$modelName}\n" .
            "    {\n" .
            "        \$model = {$modelName}::create(\$data);\n" .
            "        \$this->clearCache(\$this->cachePrefix . '*');\n" .
            "        return \$model;\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Update record\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @param array \$data\n" .
            "     * @return {$modelName}|null\n" .
            "     */\n" .
            "    public function update(int \$id, array \$data): ?{$modelName}\n" .
            "    {\n" .
            "        \$model = \$this->findById(\$id);\n" .
            "        \n" .
            "        if (!\$model) {\n" .
            "            return null;\n" .
            "        }\n" .
            "        \n" .
            "        \$model->update(\$data);\n" .
            "        \$this->clearCache(\$this->cachePrefix . '*');\n" .
            "        \$this->clearCache(\$this->cachePrefix . \$id);\n" .
            "        return \$model;\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Delete record\n" .
            "     *\n" .
            "     * @param int \$id\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function delete(int \$id): bool\n" .
            "    {\n" .
            "        \$model = \$this->findById(\$id);\n" .
            "        \n" .
            "        if (!\$model) {\n" .
            "            return false;\n" .
            "        }\n" .
            "        \n" .
            "        \$result = \$model->delete();\n" .
            "        \$this->clearCache(\$this->cachePrefix . '*');\n" .
            "        \$this->clearCache(\$this->cachePrefix . \$id);\n" .
            "        return \$result;\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Get cached data\n" .
            "     *\n" .
            "     * @param string \$key\n" .
            "     * @param \\Closure \$callback\n" .
            "     * @param int \$ttl Time to live in seconds\n" .
            "     * @return mixed\n" .
            "     */\n" .
            "    public function getCached(string \$key, \\Closure \$callback, int \$ttl = 3600)\n" .
            "    {\n" .
            "        return Cache::remember(\$key, \$ttl, \$callback);\n" .
            "    }\n\n" .

            "    /**\n" .
            "     * Clear cache for a specific key or pattern\n" .
            "     *\n" .
            "     * @param string \$key\n" .
            "     * @return bool\n" .
            "     */\n" .
            "    public function clearCache(string \$key): bool\n" .
            "    {\n" .
            "        if (str_contains(\$key, '*')) {\n" .
            "            // Pattern matching needs custom handling depending on your cache driver\n" .
            "            // For simplicity, clearing all cache for this implementation\n" .
            "            Cache::flush();\n" .
            "            return true;\n" .
            "        }\n" .
            "        \n" .
            "        return Cache::forget(\$key);\n" .
            "    }\n" .
            "}\n";
    }

    /**
     * Register the repository binding in AppServiceProvider
     */
    protected function registerRepositoryBinding($interfaceName, $implementationName)
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (!File::exists($providerPath)) {
            $this->warn("AppServiceProvider.php not found. Please register the repository binding manually.");
            return;
        }

        $content = File::get($providerPath);

        // Check if the binding already exists
        if (Str::contains($content, "\\App\\Repositories\\Contracts\\{$interfaceName}::class")) {
            $this->info("Repository binding already exists in AppServiceProvider.");
            return;
        }

        // Add the binding to the register method
        $pattern = '/(public\s+function\s+register\(\).*?\{)/s';
        $replacement = "$1\n        \$this->app->bind(\n            \\App\\Repositories\\Contracts\\{$interfaceName}::class,\n            \\App\\Repositories\\Eloquent\\{$implementationName}::class\n        );\n";

        $updatedContent = preg_replace($pattern, $replacement, $content);

        if ($updatedContent === $content) {
            $this->warn("Could not add repository binding to AppServiceProvider. Please add it manually:\n\n");
            $this->line(
                "// In AppServiceProvider.php register() method\n" .
                    "\$this->app->bind(\n" .
                    "    \\App\\Repositories\\Contracts\\{$interfaceName}::class,\n" .
                    "    \\App\\Repositories\\Eloquent\\{$implementationName}::class\n" .
                    ");"
            );
            return;
        }

        File::put($providerPath, $updatedContent);
        $this->info("Repository binding added to AppServiceProvider.");
    }
}
