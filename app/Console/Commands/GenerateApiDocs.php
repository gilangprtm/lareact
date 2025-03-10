<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:docs {--regenerate : Whether to regenerate the docs}';
    protected $description = 'Generate API documentation with dynamic routes';

    public function handle()
    {
        // Generate the initial documentation
        if ($this->option('regenerate')) {
            $this->info('Regenerating the OpenAPI documentation...');
            Artisan::call('l5-swagger:generate');
        }

        // Path to the generated JSON file
        $jsonPath = storage_path('api-docs/api-docs.json');

        if (!File::exists($jsonPath)) {
            $this->error('API docs not found. Please run l5-swagger:generate first.');
            return 1;
        }

        // Read the JSON file
        $apiDoc = json_decode(File::get($jsonPath), true);

        // Replace hardcoded paths with route URLs
        $this->replacePathsWithRoutes($apiDoc);

        // Write the updated JSON back to file
        File::put($jsonPath, json_encode($apiDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('API documentation updated with dynamic routes!');
        return 0;
    }

    protected function replacePathsWithRoutes(&$apiDoc)
    {
        // Create a mapping of all API routes
        $apiRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->getName() ?? '', 'api.');
        })->keyBy(function ($route) {
            // e.g., 'api.categories.index' => '/api/v1/categories'
            return $route->getName();
        })->map(function ($route) {
            $url = '/' . $route->uri();
            // Replace {parameter} with {parameter} without regex constraints
            $url = preg_replace('/{([^:}]+)(:.*)?}/', '{$1}', $url);
            return $url;
        })->toArray();

        // Debug info
        $this->info("Available API routes:");
        foreach ($apiRoutes as $name => $path) {
            $this->line("  {$name} => {$path}");
        }

        // Get endpoint path patterns (without version prefix)
        $pathPatterns = [];
        foreach ($apiRoutes as $routeName => $path) {
            // Extract the endpoint pattern by removing prefix like /api/v1/
            $segments = explode('/', trim($path, '/'));
            if (count($segments) >= 3 && $segments[0] === 'api') {
                // Skip version and form a basic pattern
                array_shift($segments); // remove "api"
                array_shift($segments); // remove version
                $pattern = '/api/' . implode('/', $segments);
                $pathPatterns[$pattern] = $path;
            }
        }

        // Debug path patterns
        $this->info("Path patterns mapping:");
        foreach ($pathPatterns as $pattern => $path) {
            $this->line("  {$pattern} => {$path}");
        }

        // Get all paths from the API documentation
        if (isset($apiDoc['paths'])) {
            $paths = $apiDoc['paths'];
            $newPaths = [];

            // Loop through all paths and check if we have a matching pattern
            foreach ($paths as $path => $pathData) {
                $routeName = $this->findRouteNameForPath($path, $apiRoutes);

                // Direct match by route name
                if ($routeName) {
                    $newPath = $apiRoutes[$routeName];
                    $this->info("Replacing via route name: {$path} with {$newPath}");
                    $newPaths[$newPath] = $pathData;
                }
                // Try pattern matching
                elseif (isset($pathPatterns[$path])) {
                    $newPath = $pathPatterns[$path];
                    $this->info("Replacing via pattern match: {$path} with {$newPath}");
                    $newPaths[$newPath] = $pathData;
                }
                // Try pattern matching with parameter variations
                else {
                    $foundMatch = false;

                    foreach ($pathPatterns as $pattern => $targetPath) {
                        // Replace variables with regex pattern for matching
                        $patternRegex = preg_replace('/{([^}]+)}/', '([^/]+)', $pattern);
                        $patternRegex = '#^' . $patternRegex . '$#';

                        if (preg_match($patternRegex, $path)) {
                            $this->info("Replacing via regex: {$path} with {$targetPath}");
                            $newPaths[$targetPath] = $pathData;
                            $foundMatch = true;
                            break;
                        }
                    }

                    if (!$foundMatch) {
                        // Keep the original path if no match found
                        $this->warn("No matching route found for path: {$path}");
                        $newPaths[$path] = $pathData;
                    }
                }
            }

            // Update the paths in the API doc
            $apiDoc['paths'] = $newPaths;
        }
    }

    protected function findRouteNameForPath($path, $apiRoutes)
    {
        // Remove the leading slash
        $path = ltrim($path, '/');

        // Try to match the path with the routes
        foreach ($apiRoutes as $routeName => $routePath) {
            // Simple string match after removing the leading slash
            $simplePath = ltrim($routePath, '/');

            // Check if the path matches the route
            if (
                $simplePath === $path ||
                $this->comparePathPatterns($simplePath, $path)
            ) {
                return $routeName;
            }
        }

        return null;
    }

    protected function comparePathPatterns($routePath, $docPath)
    {
        // Remove parameters and compare the base paths
        $routeBase = preg_replace('/{.*?}/', '{}', $routePath);
        $docBase = preg_replace('/{.*?}/', '{}', $docPath);

        return $routeBase === $docBase;
    }
}
