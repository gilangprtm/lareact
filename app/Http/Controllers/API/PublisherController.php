<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\PublisherController as DBPublisherController;
use App\Http\Requests\API\PublisherRequest;
use App\Http\Resources\API\PublisherResource;
use App\Models\Publisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="publishers",
 *     description="API Endpoints for Publisher Management"
 * )
 */
class PublisherController extends ApiController
{
    public function __construct(
        protected DBPublisherController $dbController
    ) {}

    /**
     * Get all publishers.
     *
     * @OA\Get(
     *     path="/api/v1/publishers",
     *     summary="Retrieve all publishers",
     *     description="Get a paginated list of all publishers with optional filters",
     *     operationId="getpublishers",
     *     tags={"publishers"},
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
     *         description="List of publishers",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="publishers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Publisher")
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
                $this->successResponse($result, 'publishers retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to retrieve publishers', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Get a specific publisher by ID.
     *
     * @OA\Get(
     *     path="/api/v1/publishers/{id}",
     *     summary="Get publisher by ID",
     *     description="Returns a single publisher",
     *     operationId="getPublisherById",
     *     tags={"publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of publisher to return",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="publisher retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Publisher"
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
                $this->successResponse(new PublisherResource($result), 'publisher retrieved successfully')
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('publisher not found', $e->getMessage()),
                404
            );
        }
    }

    /**
     * Create a new publisher.
     *
     * @OA\Post(
     *     path="/api/v1/publishers",
     *     summary="Create a new publisher",
     *     description="Creates a new publisher and returns the created resource",
     *     operationId="createPublisher",
     *     tags={"publishers"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Publisher data",
     *         @OA\JsonContent(ref="#/components/schemas/PublisherRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/PublisherRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="publisher created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="publisher created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Publisher"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function store(PublisherRequest $request): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            $data = $request->validated();
            $dto = $request->toDto();

            // File handling would go here if needed

            $result = $this->dbController->create($data);
            return response()->json(
                $this->successResponse(new PublisherResource($result), 'publisher created successfully'),
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to create publisher', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Update an existing publisher.
     *
     * @OA\Put(
     *     path="/api/v1/publishers/{id}",
     *     summary="Update a publisher",
     *     description="Updates a publisher and returns the updated resource",
     *     operationId="updatePublisher",
     *     tags={"publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of publisher to update",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Publisher data",
     *         @OA\JsonContent(ref="#/components/schemas/PublisherRequest"),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/PublisherRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="publisher updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="publisher updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Publisher"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=500, ref="#/components/responses/ServerError")
     * )
     */
    public function update(PublisherRequest $request, int $id): JsonResponse
    {
        try {
            // Get the validated data from the request DTO
            $data = $request->validated();
            $dto = $request->toDto();

            // File handling would go here if needed

            $result = $this->dbController->update($data, $id);
            return response()->json(
                $this->successResponse(new PublisherResource($result), 'publisher updated successfully')
            );
        } catch (ValidationException $e) {
            return response()->json(
                $this->errorResponse('Validation failed', $e->errors()),
                422
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('publisher not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to update publisher', $e->getMessage()),
                500
            );
        }
    }

    /**
     * Delete a publisher.
     *
     * @OA\Delete(
     *     path="/api/v1/publishers/{id}",
     *     summary="Delete a publisher",
     *     description="Deletes a publisher",
     *     operationId="deletePublisher",
     *     tags={"publishers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of publisher to delete",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="publisher deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="publisher deleted successfully"),
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
                $this->successResponse(['success' => $result], 'publisher deleted successfully')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                $this->errorResponse('publisher not found', $e->getMessage()),
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                $this->errorResponse('Failed to delete publisher', $e->getMessage()),
                500
            );
        }
    }
}
