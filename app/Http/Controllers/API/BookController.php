<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\BookController as DBBookController;
use App\Http\Requests\API\BookRequest;
use App\Http\Resources\API\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="books",
 *     description="API Endpoints for Book Management"
 * )
 */
class BookController extends ApiController
{
    public function __construct(
        protected DBBookController $dbController
    ) {}

    /**
     * Get all books.
     *
     * @OA\Get(
     *     path="/api/v1/books",
     *     summary="Retrieve all books",
     *     description="Get a paginated list of all books with optional filters",
     *     operationId="getbooks",
     *     tags={"books"},
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
     *         description="List of books",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="books retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Book")
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
                $this->successResponse($result, 'books retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to retrieve books', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Get a specific book by ID.
     *
     * @OA\Get(
     *     path="/api/v1/books/{id}",
     *     summary="Get book by ID",
     *     description="Returns a single book",
     *     operationId="getBookById",
     *     tags={"books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of book to return",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="book retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
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
                $this->successResponse(new BookResource($result), 'book retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('book not found', $e->getMessage()),
                404
            );
        }
    }

    /**
     * Create a new book.
     *
     * @OA\Post(
     *     path="/api/v1/books",
     *     summary="Create a new book",
     *     description="Creates a new book and returns the created resource",
     *     operationId="createBook",
     *     tags={"books"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Book data",
     *         @OA\JsonContent(ref="#/components/schemas/BookRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/BookRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="book created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="book created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(BookRequest $request): JsonResponse
    {
        try {
            // Get the validated data from the request
            $data = $request->validated();

            // Service layer sudah menangani file handling
            $result = $this->dbController->create($data);

            return response()->json(
                $this->successResponse(new BookResource($result), 'book created successfully'),
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to create book', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Update an existing book.
     *
     * @OA\Put(
     *     path="/api/v1/books/{id}",
     *     summary="Update a book",
     *     description="Updates a book and returns the updated resource",
     *     operationId="updateBook",
     *     tags={"books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of book to update",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Book data",
     *         @OA\JsonContent(ref="#/components/schemas/BookRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/BookRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="book updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="book updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Book"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(BookRequest $request, int $id): JsonResponse
    {
        try {
            // Get the validated data from the request
            $data = $request->validated();

            // Service layer sudah menangani file handling
            $result = $this->dbController->update($data, $id);

            return response()->json(
                $this->successResponse(new BookResource($result), 'book updated successfully')
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to update book', $e->getMessage()),
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500
            );
        }
    }

    /**
     * Delete a book.
     *
     * @OA\Delete(
     *     path="/api/v1/books/{id}",
     *     summary="Delete a book",
     *     description="Deletes a book",
     *     operationId="deleteBook",
     *     tags={"books"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of book to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="book deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="book deleted successfully"),
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
                $this->successResponse(['success' => $result], 'book deleted successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('book not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to delete book', $e->getMessage()),
                500
            );
        }
    }
}
