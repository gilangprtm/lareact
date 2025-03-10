<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class GenerateFullModuleFromModel extends Command
{
    protected $signature = 'make:module {model : The model class name}
                          {--with-web : Generate Web Controllers and views}
                          {--with-api : Generate API Controllers and resources (default: true)}
                          {--inertia : Use Inertia.js for web UI (default if with-web is set)} 
                          {--blade : Use Blade templates instead of Inertia.js}
                          {--prefix= : Route prefix for resource routes}
                          {--simple : Generate simplified architecture for smaller projects}
                          {--advanced : Generate advanced architecture with repositories for complex projects}
                          {--force : Overwrite existing files}';

    protected $description = 'Generate a full module (DTO, Service, Controllers, etc) from a Model';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelName = $this->argument('model');
        $withWeb = $this->option('with-web');
        $withApi = $this->option('with-api') !== false; // Default to true
        $useInertia = !$this->option('blade');
        $routePrefix = $this->option('prefix');
        $force = $this->option('force');
        $simple = $this->option('simple');
        $advanced = $this->option('advanced');

        // Validate options
        if ($simple && $advanced) {
            $this->error("Cannot use both --simple and --advanced options at the same time!");
            return 1;
        }

        // Get architecture mode
        $mode = $this->getArchitectureMode($simple, $advanced);

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

            $this->info("Generating {$mode} module for {$modelShortName}...");

            // Step 1: Generate DTO
            $this->info("\n[ 1/7 ] Generating DTO...");
            $this->call('make:dto', [
                'model' => $modelShortName,
                '--force' => $force
            ]);

            // Step 2: Generate Request Trait and Base Resource
            $this->info("\n[ 2/7 ] Generating Request Trait...");
            $this->call('make:request-trait', [
                'dto' => "{$modelShortName}RequestDto",
                '--force' => $force
            ]);

            $this->info("\n[ 3/7 ] Generating Base Resource...");
            $this->call('make:base-resource', [
                'dto' => "{$modelShortName}Dto",
                '--force' => $force
            ]);

            // Step 4: Generate Repository if in advanced mode
            if ($mode === 'advanced') {
                $this->info("\n[ 4/7 ] Generating Repository...");
                // If we have a make:repository command:
                if ($this->isCommandAvailable('make:repository')) {
                    $this->call('make:repository', [
                        'model' => $modelShortName,
                        '--force' => $force
                    ]);
                } else {
                    $this->warn("Repository generator not available. Skipping repository generation.");
                }
            }

            // Step 5: Generate Service
            $this->info("\n[ " . ($mode === 'advanced' ? "5/8" : "4/7") . " ] Generating Service...");
            $this->call('make:service', [
                'model' => $modelShortName,
                '--' . $mode => true,
                '--force' => $force
            ]);

            // Step 6: Generate DB Controller, Request, Resource
            $this->info("\n[ " . ($mode === 'advanced' ? "6/8" : "5/7") . " ] Generating DB Controller, Request, and Resource...");
            $this->call('make:db-controller', [
                'model' => $modelShortName,
                '--' . $mode => true,
                '--force' => $force
            ]);

            // Step 7: Generate Web Controller if requested
            if ($withWeb) {
                $this->info("\n[ " . ($mode === 'advanced' ? "7/8" : "6/7") . " ] Generating Web Controller...");
                $webControllerOptions = [
                    'model' => $modelShortName,
                    '--' . $mode => true,
                    '--force' => $force
                ];

                if ($this->option('blade')) {
                    $webControllerOptions['--blade'] = true;
                }

                if ($routePrefix) {
                    $webControllerOptions['--prefix'] = $routePrefix;
                }

                $this->call('make:web-controller', $webControllerOptions);
            } else {
                $this->info("\n[ " . ($mode === 'advanced' ? "7/8" : "6/7") . " ] Skipping Web Controller (--with-web not specified)");
            }

            // Step 8: Generate API Controller and Resources if requested
            if ($withApi) {
                $this->info("\n[ " . ($mode === 'advanced' ? "8/8" : "7/7") . " ] Generating API Controller and Resources...");

                // Generate API Request and Resource
                $this->call('make:api-classes', [
                    'dto' => "{$modelShortName}Dto",
                    '--' . $mode => true,
                    '--force' => $force
                ]);

                // Generate API Controller
                $this->call('make:api-controller', [
                    'entity' => $modelShortName,
                    '--' . $mode => true,
                    '--force' => $force
                ]);
            } else {
                $this->info("\n[ " . ($mode === 'advanced' ? "8/8" : "7/7") . " ] Skipping API Controller and Resources (--with-api=false)");
            }

            $this->info("\nModule generation complete! ✓");
            $this->displayArchitectureInfo($mode, $modelShortName);

            return 0;
        } catch (\Exception $e) {
            $this->error("Error generating module: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Get the architecture mode based on options
     */
    protected function getArchitectureMode($simple, $advanced)
    {
        if ($simple) {
            return 'simple';
        } elseif ($advanced) {
            return 'advanced';
        } else {
            return 'standard';
        }
    }

    /**
     * Check if a command is available
     */
    protected function isCommandAvailable($command)
    {
        $commands = array_keys($this->getApplication()->all());
        return in_array($command, $commands);
    }

    /**
     * Display architecture information
     */
    protected function displayArchitectureInfo($mode, $modelName)
    {
        $this->line("\n<fg=blue;options=bold>ARCHITECTURE INFORMATION:</>");

        switch ($mode) {
            case 'simple':
                $this->line("\n<fg=green>✓ Simple Architecture Generated</>");
                $this->line("  Ideal for: Small to medium projects with straightforward business logic.");
                $this->line("  Features:");
                $this->line("   - Streamlined DTO structure");
                $this->line("   - Basic Service implementation");
                $this->line("   - Direct model access without Repository layer");
                $this->line("\n  Usage Tips:");
                $this->line("   - For simple CRUD operations, you can use the Service directly");
                $this->line("   - As your app grows, consider gradually moving to standard architecture");
                break;

            case 'standard':
                $this->line("\n<fg=green>✓ Standard Architecture Generated</>");
                $this->line("  Ideal for: Medium projects with moderate business logic complexity.");
                $this->line("  Features:");
                $this->line("   - Full DTO layer for domain data representation");
                $this->line("   - Service layer for business logic");
                $this->line("   - Separation of concerns through multiple layers");
                $this->line("\n  Usage Tips:");
                $this->line("   - Keep business logic in the Service layer");
                $this->line("   - Use DTOs for data transfer between layers");
                $this->line("   - Use Request objects for validation");
                $this->line("   - Use Resources for API responses");
                break;

            case 'advanced':
                $this->line("\n<fg=green>✓ Advanced Architecture Generated</>");
                $this->line("  Ideal for: Large, complex projects with sophisticated business logic.");
                $this->line("  Features:");
                $this->line("   - Comprehensive DTO layer with domain validation");
                $this->line("   - Repository pattern for data access abstraction");
                $this->line("   - Service layer with advanced business logic hooks");
                $this->line("   - Event-driven architecture support");
                $this->line("\n  Usage Tips:");
                $this->line("   - Use Repositories for database operations");
                $this->line("   - Implement domain events for complex workflows");
                $this->line("   - Consider adding unit tests for each layer");
                break;
        }

        $this->line("\n<fg=yellow>Next Steps:</>");
        $this->line("  1. Review the generated code and customize as needed");
        $this->line("  2. Add your business logic to the Service layer");
        $this->line("  3. Configure routes in routes/api.php and routes/web.php");
        $this->line("  4. Run tests to ensure everything works as expected");

        $this->line("\n<fg=blue>For more information, see the documentation at:</> docs/generator-workflow.md");
    }
}
