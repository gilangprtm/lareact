<?php

namespace App\Http\Controllers;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="API Documentation",
 *         description="Dokumentasi API dengan Swagger OpenAPI",
 *         @OA\Contact(
 *             name="Your Name",
 *             email="your@email.com"
 *         ),
 *     ),
 *     @OA\Server(
 *         url=L5_SWAGGER_CONST_HOST,
 *         description="API Server"
 *     ),
 *     @OA\Components(
 *         @OA\Response(
 *             response="ValidationError",
 *             description="Validation errors",
 *             @OA\JsonContent(
 *                 @OA\Property(property="status", type="string", example="error"),
 *                 @OA\Property(property="message", type="string", example="Validation failed"),
 *                 @OA\Property(property="errors", type="object", example={"field": {"Error message"}})
 *             )
 *         ),
 *         @OA\Response(
 *             response="NotFound",
 *             description="Resource not found",
 *             @OA\JsonContent(
 *                 @OA\Property(property="status", type="string", example="error"),
 *                 @OA\Property(property="message", type="string", example="Resource not found"),
 *                 @OA\Property(property="error", type="string", example="Resource with ID 1 not found")
 *             )
 *         ),
 *         @OA\Response(
 *             response="ServerError",
 *             description="Internal server error",
 *             @OA\JsonContent(
 *                 @OA\Property(property="status", type="string", example="error"),
 *                 @OA\Property(property="message", type="string", example="Internal server error"),
 *                 @OA\Property(property="error", type="string", example="An unexpected error occurred")
 *             )
 *         )
 *     )
 * )
 */
abstract class ApiController
{
    /**
     * Get standardized successful response structure
     *
     * @param mixed $data Data to be included in the response
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return array
     */
    protected function successResponse($data, string $message = 'Success', int $statusCode = 200): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Get standardized error response structure
     *
     * @param string $message Error message
     * @param mixed $error Detailed error information
     * @param int $statusCode HTTP status code
     * @return array
     */
    protected function errorResponse(string $message, $error = null, int $statusCode = 500): array
    {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($error) {
            $response['error'] = $error;
        }

        return $response;
    }
}
