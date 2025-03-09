<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\CategoryController as DBCategoryController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\BodyParameter;

class CategoryController extends ApiController
{
    public function __construct(
        protected DBCategoryController $dbController
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $params = $request->only([
                'search',
                'page',
                'load',
                'parent_id',
                'status',
                'has_children',
                'has_books',
                'field',
                'direction'
            ]);
            $result = $this->dbController->index($params);

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
