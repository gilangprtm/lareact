<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\PublisherController as DBPublisherController;
use App\Models\Publisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class PublisherController extends ApiController
{
    public function __construct(
        protected DBPublisherController $dbController
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $params = $request->only([
                'search',
                'page',
                'load',
                'status',
                'city',
                'state',
                'country',
                'has_books',
                'field',
                'direction'
            ]);
            $result = $this->dbController->index($params);

            // Structure the response with metadata
            return response()->json([
                'status' => 'success',
                'message' => 'Publishers retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve publishers',
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
                'message' => 'Publisher retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Publisher not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->except('logo');

            // Handle logo upload if present
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $logo = $request->file('logo');

                // Validate the image
                $validator = Validator::make(['logo' => $logo], [
                    'logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid logo file',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Store the file
                $path = $logo->store('publishers/logos', 'public');
                $data['logo_path'] = $path;
            }

            $result = $this->dbController->create($data);
            return response()->json([
                'status' => 'success',
                'message' => 'Publisher created successfully',
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
                'message' => 'Failed to create publisher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->except('logo');

            // Handle logo upload if present
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $logo = $request->file('logo');

                // Validate the image
                $validator = Validator::make(['logo' => $logo], [
                    'logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid logo file',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Delete old logo if exists
                $publisher = $this->dbController->find($id);
                if ($publisher->logo_path) {
                    Storage::disk('public')->delete($publisher->logo_path);
                }

                // Store the new file
                $path = $logo->store('publishers/logos', 'public');
                $data['logo_path'] = $path;
            }

            $result = $this->dbController->update($data, $id);
            return response()->json([
                'status' => 'success',
                'message' => 'Publisher updated successfully',
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
                'message' => 'Publisher not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update publisher',
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
                'message' => 'Publisher deleted successfully',
                'success' => $result
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Publisher not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete publisher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
