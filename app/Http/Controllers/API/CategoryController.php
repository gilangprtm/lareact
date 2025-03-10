<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\CategoryController as DBCategoryController;
use App\Http\Requests\API\CategoryRequest;
use App\Http\Resources\API\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="categories",
 *     description="API Endpoints for Category Management"
 * )
 */
class CategoryController extends ApiController
{
    public function __construct(
        protected DBCategoryController $dbController
    ) {}

    /**
     * Get all categories.
     *
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Retrieve all categories",
     *     description="Get a paginated list of all categories with optional filters",
     *     operationId="getcategories",
     *     tags={"categories"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="load",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="field",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", default="id")
     *     ),
     *     @OA\Parameter(
     *         name="direction",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="categories retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Category")
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=15),
     *                 @OA\Property(property="per_page", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $params = $request->only(['search', 'page', 'load', 'field', 'direction']);
            $result = $this->dbController->index($params);

            return response()->json(
                $this->successResponse($result, 'categories retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to retrieve categories', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Get a specific category by ID.
     *
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     summary="Get category by ID",
     *     description="Returns a single category",
     *     operationId="getCategoryById",
     *     tags={"categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to return",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="category retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Category"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->dbController->find($id);
            return response()->json(
                $this->successResponse(new CategoryResource($result), 'category retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('category not found', $e->getMessage()),
                404
            );
        }
    }

    /**
     * Create a new category.
     *
     * @OA\Post(
     *     path="/api/v1/categories",
     *     summary="Create a new category",
     *     description="Creates a new category and returns the created resource",
     *     operationId="createCategory",
     *     tags={"categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Category data",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/CategoryRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="category created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Category"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            $data = $request->validated();
            $dto = $request->toDto();

            // File handling would go here if needed

            $result = $this->dbController->create($data);
            return response()->json(
                $this->successResponse(new CategoryResource($result), 'category created successfully'),
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to create category', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Update an existing category.
     *
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     summary="Update a category",
     *     description="Updates a category and returns the updated resource",
     *     operationId="updateCategory",
     *     tags={"categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to update",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Category data",
     *         @OA\JsonContent(ref="#/components/schemas/CategoryRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/CategoryRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="category updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Category"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(CategoryRequest $request, int $id): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            $data = $request->validated();
            $dto = $request->toDto();

            // File handling would go here if needed

            $result = $this->dbController->update($data, $id);
            return response()->json(
                $this->successResponse(new CategoryResource($result), 'category updated successfully')
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('category not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to update category', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Delete a category.
     *
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     summary="Delete a category",
     *     description="Deletes a category",
     *     operationId="deleteCategory",
     *     tags={"categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of category to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="category deleted successfully"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->dbController->delete($id);
            return response()->json(
                $this->successResponse(['success' => $result], 'category deleted successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('category not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to delete category', $e->getMessage()),
                500
            );
        }
    }
}
