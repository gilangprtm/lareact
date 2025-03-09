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

        // Get all paths from the API documentation
        if (isset($apiDoc['paths'])) {
            $paths = $apiDoc['paths'];
            $newPaths = [];

            // Loop through all paths and check if we have a named route
            foreach ($paths as $path => $pathData) {
                $routeName = $this->findRouteNameForPath($path, $apiRoutes);

                if ($routeName) {
                    // We found a route, update the path
                    $newPath = $apiRoutes[$routeName];
                    $this->info("Replacing: $path with $newPath");
                    $newPaths[$newPath] = $pathData;
                } else {
                    // Keep the original path
                    $newPaths[$path] = $pathData;
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
