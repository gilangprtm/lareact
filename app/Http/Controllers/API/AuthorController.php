<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\AuthorController as DBAuthorController;
use App\Http\Requests\API\AuthorRequest;
use App\Http\Resources\API\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Authors",
 *     description="API Endpoints for Author Management"
 * )
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
     *     path="/api/authors",
     *     summary="Retrieve all authors",
     *     description="Get a paginated list of all authors with optional filters",
     *     operationId="getAuthors",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for author name or email",
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
     *         description="List of authors",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Authors retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Author")
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

            // Wrapping results in resource collection automatically
            return response()->json(
                $this->successResponse($result, 'Authors retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to retrieve authors', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Get a specific author by ID.
     *
     * @OA\Get(
     *     path="/api/authors/{id}",
     *     summary="Get author by ID",
     *     description="Returns a single author",
     *     operationId="getAuthorById",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of author to return",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Author retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Author"
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
                $this->successResponse(new AuthorResource($result), 'Author retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Author not found', $e->getMessage()),
                404
            );
        }
    }

    /**
     * Create a new author.
     *
     * @OA\Post(
     *     path="/api/authors",
     *     summary="Create a new author",
     *     description="Creates a new author and returns the created resource",
     *     operationId="createAuthor",
     *     tags={"Authors"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Author data",
     *         @OA\JsonContent(ref="#/components/schemas/AuthorRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/AuthorRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Author created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Author created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Author"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(AuthorRequest $request): JsonResponse
    {
        try {
            $data = $request->except('photo');
            $dto = $request->toDto();

            // Handle photo upload if present
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photo = $request->file('photo');

                // Validate the image
                $validator = Validator::make(['photo' => $photo], [
                    'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json(
                        $this->errorResponse('Invalid photo file', $validator->errors()),
                        422
                    );
                }

                // Store the file
                $path = $photo->store('authors/photos', 'public');
                $data['photo_path'] = $path;
            }

            $result = $this->dbController->create($data);
            return response()->json(
                $this->successResponse(new AuthorResource($result), 'Author created successfully'),
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to create author', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Update an existing author.
     *
     * @OA\Put(
     *     path="/api/authors/{id}",
     *     summary="Update an author",
     *     description="Updates an author and returns the updated resource",
     *     operationId="updateAuthor",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of author to update",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Author data",
     *         @OA\JsonContent(ref="#/components/schemas/AuthorRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/AuthorRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Author updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Author"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(AuthorRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->except('photo');
            $dto = $request->toDto();

            // Handle photo upload if present
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $photo = $request->file('photo');

                // Validate the image
                $validator = Validator::make(['photo' => $photo], [
                    'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json(
                        $this->errorResponse('Invalid photo file', $validator->errors()),
                        422
                    );
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
            return response()->json(
                $this->successResponse(new AuthorResource($result), 'Author updated successfully')
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('Author not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to update author', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Delete an author.
     *
     * @OA\Delete(
     *     path="/api/authors/{id}",
     *     summary="Delete an author",
     *     description="Deletes an author",
     *     operationId="deleteAuthor",
     *     tags={"Authors"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of author to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Author deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Author deleted successfully"),
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
            // Get the author to delete their photo if needed
            $author = $this->dbController->find($id);

            // Delete the author's photo if it exists
            if ($author->photo_path) {
                Storage::disk('public')->delete($author->photo_path);
            }

            $result = $this->dbController->delete($id);
            return response()->json(
                $this->successResponse(['success' => $result], 'Author deleted successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('Author not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to delete author', $e->getMessage()),
                500
            );
        }
    }
}
