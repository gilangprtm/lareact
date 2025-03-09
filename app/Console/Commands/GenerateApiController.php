<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateApiController extends Command
{
    protected $signature = 'make:api-controller {entity : Entity name (e.g., Author, Book, Category)}
                           {--module= : API module name (e.g., v1 for /api/v1/)}
                           {--path=app/Http/Controllers/API : Path to store the controller}
                           {--force : Overwrite existing files}';

    protected $description = 'Generate a DRY API controller with OpenAPI documentation from DTO';

    public function handle()
    {
        $entityName = $this->argument('entity');
        $module = $this->option('module') ? '\\' . $this->option('module') : '';
        $path = $this->option('path') . ($module ? '/' . str_replace('\\', '/', $module) : '');
        $force = $this->option('force');

        // Create path if not exists
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // Check if DTO exists
        $dtoClass = "App\\DTO\\{$entityName}Dto";
        if (!class_exists($dtoClass)) {
            $this->error("DTO class {$dtoClass} does not exist. Please generate it first.");
            $this->info("You can use: php artisan make:dto {$entityName}");
            return 1;
        }

        // Check if RequestDTO exists
        $requestDtoClass = "App\\DTO\\{$entityName}RequestDto";
        if (!class_exists($requestDtoClass)) {
            $this->error("Request DTO class {$requestDtoClass} does not exist. Please generate it first.");
            return 1;
        }

        // Check if Request exists
        $requestClass = "App\\Http\\Requests\\API\\{$entityName}Request";
        if (!class_exists($requestClass)) {
            $this->error("Request class {$requestClass} does not exist. Please generate it first.");
            $this->info("You can use: php artisan make:api-classes {$entityName}Dto");
            return 1;
        }

        // Check if Resource exists
        $resourceClass = "App\\Http\\Resources\\API\\{$entityName}Resource";
        if (!class_exists($resourceClass)) {
            $this->error("Resource class {$resourceClass} does not exist. Please generate it first.");
            $this->info("You can use: php artisan make:api-classes {$entityName}Dto");
            return 1;
        }

        // Generate controller file path
        $controllerName = "{$entityName}Controller";
        $controllerPath = "{$path}/{$controllerName}.php";

        // Check if file exists
        if (File::exists($controllerPath) && !$force) {
            if (!$this->confirm("Controller {$controllerName} already exists. Overwrite?")) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Generate controller
        $namespace = "App\\Http\\Controllers\\API" . $module;
        $dbControllerClass = "App\\Http\\Controllers\\DB\\{$entityName}Controller";
        $modelClass = "App\\Models\\{$entityName}";

        // Check if Model exists
        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist. Please create it first.");
            return 1;
        }

        // Determine route resource name (pluralized and snake_cased)
        $pluralName = Str::plural(Str::snake($entityName));
        $baseApiPath = $module ? strtolower(str_replace('\\', '/', $module)) . "/{$pluralName}" : $pluralName;

        // Create controller content
        $content = $this->generateControllerContent(
            $entityName,
            $namespace,
            $dbControllerClass,
            $requestClass,
            $resourceClass,
            $modelClass,
            $baseApiPath
        );

        // Write to file
        File::put($controllerPath, $content);

        $this->info("Controller {$controllerName} generated successfully at {$controllerPath}");

        // Show route suggestions
        $this->info("\nAdd these routes to your api.php:");
        $this->info("Route::apiResource('{$baseApiPath}', \\{$namespace}\\{$controllerName}::class);");

        return 0;
    }

    /**
     * Generate controller content
     */
    protected function generateControllerContent(
        $entityName,
        $namespace,
        $dbControllerClass,
        $requestClass,
        $resourceClass,
        $modelClass,
        $baseApiPath
    ) {
        $modelShortName = class_basename($modelClass);
        $dbControllerShortName = "DB{$entityName}Controller";
        $resourceShortName = "{$entityName}Resource";
        $requestShortName = "{$entityName}Request";
        $pluralVar = Str::camel(Str::plural($entityName));
        $singularVar = Str::camel($entityName);

        return "<?php

namespace {$namespace};

use App\\Http\\Controllers\\ApiController;
use {$dbControllerClass} as {$dbControllerShortName};
use {$requestClass};
use {$resourceClass};
use {$modelClass};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\ResourceCollection;
use Illuminate\\Validation\\ValidationException;
use Illuminate\\Support\\Facades\\Storage;

/**
 * @OA\\Tag(
 *     name=\"{$pluralVar}\",
 *     description=\"API Endpoints for {$entityName} Management\"
 * )
 */
class {$entityName}Controller extends ApiController
{
    public function __construct(
        protected {$dbControllerShortName} \$dbController
    ) {}

    /**
     * Get all {$pluralVar}.
     *
     * @OA\\Get(
     *     path=\"/api/{$baseApiPath}\",
     *     summary=\"Retrieve all {$pluralVar}\",
     *     description=\"Get a paginated list of all {$pluralVar} with optional filters\",
     *     operationId=\"get{$pluralVar}\",
     *     tags={\"{$pluralVar}\"},
     *     @OA\\Parameter(
     *         name=\"search\",
     *         in=\"query\",
     *         description=\"Search term\",
     *         required=false,
     *         @OA\\Schema(type=\"string\")
     *     ),
     *     @OA\\Parameter(
     *         name=\"page\",
     *         in=\"query\",
     *         description=\"Page number\",
     *         required=false,
     *         @OA\\Schema(type=\"integer\", default=1)
     *     ),
     *     @OA\\Parameter(
     *         name=\"load\",
     *         in=\"query\",
     *         description=\"Items per page\",
     *         required=false,
     *         @OA\\Schema(type=\"integer\", default=10)
     *     ),
     *     @OA\\Parameter(
     *         name=\"field\",
     *         in=\"query\",
     *         description=\"Field to sort by\",
     *         required=false,
     *         @OA\\Schema(type=\"string\", default=\"id\")
     *     ),
     *     @OA\\Parameter(
     *         name=\"direction\",
     *         in=\"query\",
     *         description=\"Sort direction\",
     *         required=false,
     *         @OA\\Schema(type=\"string\", enum={\"asc\", \"desc\"}, default=\"desc\")
     *     ),
     *     @OA\\Response(
     *         response=200,
     *         description=\"List of {$pluralVar}\",
     *         @OA\\JsonContent(
     *             @OA\\Property(property=\"status\", type=\"string\", example=\"success\"),
     *             @OA\\Property(property=\"message\", type=\"string\", example=\"{$pluralVar} retrieved successfully\"),
     *             @OA\\Property(
     *                 property=\"data\",
     *                 type=\"object\",
     *                 @OA\\Property(property=\"current_page\", type=\"integer\", example=1),
     *                 @OA\\Property(
     *                     property=\"data\",
     *                     type=\"array\",
     *                     @OA\\Items(ref=\"#/components/schemas/{$entityName}\")
     *                 ),
     *                 @OA\\Property(property=\"total\", type=\"integer\", example=15),
     *                 @OA\\Property(property=\"per_page\", type=\"integer\", example=10)
     *             )
     *         )
     *     ),
     *     @OA\\Response(response=500, ref=\"#/components/responses/ServerError\")
     * )
     */
    public function index(Request \$request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            \$params = \$request->only(['search', 'page', 'load', 'field', 'direction']);
            \$result = \$this->dbController->index(\$params);

            return response()->json(
                \$this->successResponse(\$result, '{$pluralVar} retrieved successfully')
            );
        } catch (\\Exception \$e) {
            return response()->json(
                \$this->errorResponse('Failed to retrieve {$pluralVar}', \$e->getMessage()),
                500
            );
        }
    }

    /**
     * Get a specific {$singularVar} by ID.
     *
     * @OA\\Get(
     *     path=\"/api/{$baseApiPath}/{id}\",
     *     summary=\"Get {$singularVar} by ID\",
     *     description=\"Returns a single {$singularVar}\",
     *     operationId=\"get{$entityName}ById\",
     *     tags={\"{$pluralVar}\"},
     *     @OA\\Parameter(
     *         name=\"id\",
     *         in=\"path\",
     *         description=\"ID of {$singularVar} to return\",
     *         required=true,
     *         @OA\\Schema(type=\"integer\", format=\"int64\")
     *     ),
     *     @OA\\Response(
     *         response=200,
     *         description=\"Successful operation\",
     *         @OA\\JsonContent(
     *             @OA\\Property(property=\"status\", type=\"string\", example=\"success\"),
     *             @OA\\Property(property=\"message\", type=\"string\", example=\"{$singularVar} retrieved successfully\"),
     *             @OA\\Property(
     *                 property=\"data\",
     *                 ref=\"#/components/schemas/{$entityName}\"
     *             )
     *         )
     *     ),
     *     @OA\\Response(response=404, ref=\"#/components/responses/NotFound\"),
     *     @OA\\Response(response=500, ref=\"#/components/responses/ServerError\")
     * )
     */
    public function show(int \$id): JsonResponse
    {
        try {
            \$result = \$this->dbController->find(\$id);
            return response()->json(
                \$this->successResponse(new {$resourceShortName}(\$result), '{$singularVar} retrieved successfully')
            );
        } catch (\\Exception \$e) {
            return response()->json(
                \$this->errorResponse('{$singularVar} not found', \$e->getMessage()),
                404
            );
        }
    }

    /**
     * Create a new {$singularVar}.
     *
     * @OA\\Post(
     *     path=\"/api/{$baseApiPath}\",
     *     summary=\"Create a new {$singularVar}\",
     *     description=\"Creates a new {$singularVar} and returns the created resource\",
     *     operationId=\"create{$entityName}\",
     *     tags={\"{$pluralVar}\"},
     *     @OA\\RequestBody(
     *         required=true,
     *         description=\"{$entityName} data\",
     *         @OA\\JsonContent(ref=\"#/components/schemas/{$entityName}Request\"),
     *         @OA\\MediaType(
     *             mediaType=\"multipart/form-data\",
     *             @OA\\Schema(ref=\"#/components/schemas/{$entityName}Request\")
     *         )
     *     ),
     *     @OA\\Response(
     *         response=201,
     *         description=\"{$singularVar} created successfully\",
     *         @OA\\JsonContent(
     *             @OA\\Property(property=\"status\", type=\"string\", example=\"success\"),
     *             @OA\\Property(property=\"message\", type=\"string\", example=\"{$singularVar} created successfully\"),
     *             @OA\\Property(
     *                 property=\"data\",
     *                 ref=\"#/components/schemas/{$entityName}\"
     *             )
     *         )
     *     ),
     *     @OA\\Response(response=422, ref=\"#/components/responses/ValidationError\"),
     *     @OA\\Response(response=500, ref=\"#/components/responses/ServerError\")
     * )
     */
    public function store({$requestShortName} \$request): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            \$data = \$request->validated();
            \$dto = \$request->toDto();
            
            // File handling would go here if needed
            
            \$result = \$this->dbController->create(\$data);
            return response()->json(
                \$this->successResponse(new {$resourceShortName}(\$result), '{$singularVar} created successfully'),
                201
            );
        } catch (ValidationException \$e) {
            return response()->json(
                \$this->errorResponse('Validation failed', \$e->errors()),
                422
            );
        } catch (\\Exception \$e) {
            return response()->json(
                \$this->errorResponse('Failed to create {$singularVar}', \$e->getMessage()),
                500
            );
        }
    }

    /**
     * Update an existing {$singularVar}.
     *
     * @OA\\Put(
     *     path=\"/api/{$baseApiPath}/{id}\",
     *     summary=\"Update a {$singularVar}\",
     *     description=\"Updates a {$singularVar} and returns the updated resource\",
     *     operationId=\"update{$entityName}\",
     *     tags={\"{$pluralVar}\"},
     *     @OA\\Parameter(
     *         name=\"id\",
     *         in=\"path\",
     *         description=\"ID of {$singularVar} to update\",
     *         required=true,
     *         @OA\\Schema(type=\"integer\", format=\"int64\")
     *     ),
     *     @OA\\RequestBody(
     *         required=true,
     *         description=\"{$entityName} data\",
     *         @OA\\JsonContent(ref=\"#/components/schemas/{$entityName}Request\"),
     *         @OA\\MediaType(
     *             mediaType=\"multipart/form-data\",
     *             @OA\\Schema(ref=\"#/components/schemas/{$entityName}Request\")
     *         )
     *     ),
     *     @OA\\Response(
     *         response=200,
     *         description=\"{$singularVar} updated successfully\",
     *         @OA\\JsonContent(
     *             @OA\\Property(property=\"status\", type=\"string\", example=\"success\"),
     *             @OA\\Property(property=\"message\", type=\"string\", example=\"{$singularVar} updated successfully\"),
     *             @OA\\Property(
     *                 property=\"data\",
     *                 ref=\"#/components/schemas/{$entityName}\"
     *             )
     *         )
     *     ),
     *     @OA\\Response(response=404, ref=\"#/components/responses/NotFound\"),
     *     @OA\\Response(response=422, ref=\"#/components/responses/ValidationError\"),
     *     @OA\\Response(response=500, ref=\"#/components/responses/ServerError\")
     * )
     */
    public function update({$requestShortName} \$request, int \$id): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            \$data = \$request->validated();
            \$dto = \$request->toDto();
            
            // File handling would go here if needed
            
            \$result = \$this->dbController->update(\$data, \$id);
            return response()->json(
                \$this->successResponse(new {$resourceShortName}(\$result), '{$singularVar} updated successfully')
            );
        } catch (ValidationException \$e) {
            return response()->json(
                \$this->errorResponse('Validation failed', \$e->errors()),
                422
            );
        } catch (\\Illuminate\\Database\\Eloquent\\ModelNotFoundException \$e) {
            return response()->json(
                \$this->errorResponse('{$singularVar} not found', \$e->getMessage()),
                404
            );
        } catch (\\Exception \$e) {
            return response()->json(
                \$this->errorResponse('Failed to update {$singularVar}', \$e->getMessage()),
                500
            );
        }
    }

    /**
     * Delete a {$singularVar}.
     *
     * @OA\\Delete(
     *     path=\"/api/{$baseApiPath}/{id}\",
     *     summary=\"Delete a {$singularVar}\",
     *     description=\"Deletes a {$singularVar}\",
     *     operationId=\"delete{$entityName}\",
     *     tags={\"{$pluralVar}\"},
     *     @OA\\Parameter(
     *         name=\"id\",
     *         in=\"path\",
     *         description=\"ID of {$singularVar} to delete\",
     *         required=true,
     *         @OA\\Schema(type=\"integer\", format=\"int64\")
     *     ),
     *     @OA\\Response(
     *         response=200,
     *         description=\"{$singularVar} deleted successfully\",
     *         @OA\\JsonContent(
     *             @OA\\Property(property=\"status\", type=\"string\", example=\"success\"),
     *             @OA\\Property(property=\"message\", type=\"string\", example=\"{$singularVar} deleted successfully\"),
     *             @OA\\Property(property=\"success\", type=\"boolean\", example=true)
     *         )
     *     ),
     *     @OA\\Response(response=404, ref=\"#/components/responses/NotFound\"),
     *     @OA\\Response(response=500, ref=\"#/components/responses/ServerError\")
     * )
     */
    public function destroy(int \$id): JsonResponse
    {
        try {
            \$result = \$this->dbController->delete(\$id);
            return response()->json(
                \$this->successResponse(['success' => \$result], '{$singularVar} deleted successfully')
            );
        } catch (\\Illuminate\\Database\\Eloquent\\ModelNotFoundException \$e) {
            return response()->json(
                \$this->errorResponse('{$singularVar} not found', \$e->getMessage()),
                404
            );
        } catch (\\Exception \$e) {
            return response()->json(
                \$this->errorResponse('Failed to delete {$singularVar}', \$e->getMessage()),
                500
            );
        }
    }
}
";
    }
}
