<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\AuthorController as DBAuthorController;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Authors
 * @group Author Management
 */
class AuthorController extends Controller
{
    public function __construct(
        protected DBAuthorController $dbController
    ) {}

    /**
     * Get a paginated list of authors with filtering options
     * 
     * Query parameters:
     * - search: Search term for author name or email
     * - page: Page number for pagination (default: 1)
     * - load: Number of items per page (default: 10)
     * - field: Field to sort by (default: id)
     * - direction: Sort direction (asc or desc, default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $result = $this->dbController->index();

            // Structure the response with metadata
            return response()->json([
                'status' => 'success',
                'message' => 'Authors retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve authors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get author details by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->dbController->find($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Author retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Author not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new author
     * 
     * Required fields:
     * - name: Author's name
     * - email: Author's email
     * 
     * Optional fields:
     * - bio: Author's biography
     * - birth_date: Author's birth date (YYYY-MM-DD)
     * - website: Author's website URL
     * - photo: Author's photo (file upload)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->except('photo');

            // Handle photo upload if present
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photo = $request->file('photo');

                // Validate the image
                $validator = Validator::make(['photo' => $photo], [
                    'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid photo file',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Store the file
                $path = $photo->store('authors/photos', 'public');
                $data['photo_path'] = $path;
            }

            $result = $this->dbController->create($data);
            return response()->json([
                'status' => 'success',
                'message' => 'Author created successfully',
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
                'message' => 'Failed to create author',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing author
     * 
     * Optional fields:
     * - name: Author's name
     * - email: Author's email
     * - bio: Author's biography
     * - birth_date: Author's birth date (YYYY-MM-DD)
     * - website: Author's website URL
     * - photo: Author's photo (file upload)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->except('photo');

            // Handle photo upload if present
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photo = $request->file('photo');

                // Validate the image
                $validator = Validator::make(['photo' => $photo], [
                    'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid photo file',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Delete old photo if exists
                $author = $this->dbController->find($id);
                if ($author->photo_path) {
                    Storage::disk('public')->delete($author->photo_path);
                }

                // Store the new file
                $path = $photo->store('authors/photos', 'public');
                $data['photo_path'] = $path;
            }

            $result = $this->dbController->update($data, $id);
            return response()->json([
                'status' => 'success',
                'message' => 'Author updated successfully',
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
                'message' => 'Author not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update author',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an author
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Get the author to delete their photo if needed
            $author = $this->dbController->find($id);

            // Delete the author's photo if it exists
            if ($author->photo_path) {
                Storage::disk('public')->delete($author->photo_path);
            }

            $result = $this->dbController->delete($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Author deleted successfully',
                'success' => $result
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Author not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete author',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
