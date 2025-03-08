<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\CategoryController as DBCategoryController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @tags Categories
 * @group Category Management
 */
class CategoryController extends Controller
{
    public function __construct(
        protected DBCategoryController $dbController
    ) {}

    /**
     * Get a paginated list of categories with filtering options
     * 
     * Query parameters:
     * - search: Search term for category name, slug, or description
     * - page: Page number for pagination (default: 1)
     * - load: Number of items per page (default: 10)
     * - parent_id: Filter by parent category ID
     * - status: Filter by status (active, inactive)
     * - has_children: Filter categories that have children (true/false)
     * - has_books: Filter categories that have books (true/false)
     * - field: Field to sort by (default: id)
     * - direction: Sort direction (asc or desc, default: desc)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $result = $this->dbController->index();

            // Structure the response with metadata
            return response()->json([
                'status' => 'success',
                'message' => 'Categories retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category details by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->dbController->find($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Category retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new category
     * 
     * Required fields:
     * - name: Category name
     * 
     * Optional fields:
     * - description: Category description
     * - parent_id: Parent category ID
     * - status: Category status (active, inactive)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $result = $this->dbController->create($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully',
                'data' => $result
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing category
     * 
     * Optional fields:
     * - name: Category name
     * - description: Category description
     * - parent_id: Parent category ID
     * - status: Category status (active, inactive)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->dbController->update($request->all(), $id);
            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $result
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->dbController->delete($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully',
                'success' => $result
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
