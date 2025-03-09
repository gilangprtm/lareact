<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\AuthorController as DBAuthorController;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Authors",
 * )
 * @OA\PathItem(path="/api/authors")
 */
class AuthorController extends ApiController
{
    public function __construct(
        protected DBAuthorController $dbController
    ) {}

    /**
     * Get all authors.
     *
     * @OA\Get(
     *     tags={"Authors"},
     *     path="/api/authors",
     *     summary="Retrieve authors",
     *     @OA\Response(response=200, description="List of authors",
     *         @OA\JsonContent(type="array",
     *             @OA\Items(ref="#/components/schemas/AuthorResource")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $params = $request->only(['search', 'page', 'load', 'field', 'direction']);
            $result = $this->dbController->index($params);

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
     * Create a new author.
     *
     * @OA\Post(
     *     tags={"Authors"},
     *     path="/api/authors",
     *     summary="Create a new author",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AuthorRequest")
     *     ),
     *     @OA\Response(response=201, description="Author created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AuthorResource")
     *     )
     * )
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
