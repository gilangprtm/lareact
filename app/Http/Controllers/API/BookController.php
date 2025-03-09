<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\DB\BookController as DBBookController;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class BookController extends ApiController
{
    public function __construct(
        protected DBBookController $dbController
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            // Forward all query parameters to the DB controller
            $params = $request->only(['search', 'page', 'load']);
            $result = $this->dbController->index($params);

            // Structure the response with metadata
            return response()->json([
                'status' => 'success',
                'message' => 'Books retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve books',
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
                'message' => 'Book retrieved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            // Get basic book data
            $data = $request->except(['images', 'files', 'author_ids']);

            // Validate and process author IDs
            if ($request->has('author_ids')) {
                $data['author_ids'] = is_array($request->author_ids)
                    ? $request->author_ids
                    : json_decode($request->author_ids, true);
            }

            // Create the book first
            $book = $this->dbController->create($data);

            // Handle image uploads if present
            if ($request->hasFile('images')) {
                $this->handleImageUploads($book, $request->file('images'));
            }

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $this->handleFileUploads($book, $request->file('files'));
            }

            // Get the updated book with relationships
            $result = $this->dbController->find($book->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Book created successfully',
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
                'message' => 'Failed to create book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Get the book
            $book = $this->dbController->find($id);

            // Get basic book data
            $data = $request->except(['images', 'files', 'author_ids', 'delete_images', 'delete_files']);

            // Validate and process author IDs
            if ($request->has('author_ids')) {
                $data['author_ids'] = is_array($request->author_ids)
                    ? $request->author_ids
                    : json_decode($request->author_ids, true);
            }

            // Update the book
            $this->dbController->update($data, $id);

            // Handle image uploads if present
            if ($request->hasFile('images')) {
                $this->handleImageUploads($book, $request->file('images'));
            }

            // Handle file uploads if present
            if ($request->hasFile('files')) {
                $this->handleFileUploads($book, $request->file('files'));
            }

            // Delete images if requested
            if ($request->has('delete_images')) {
                $this->deleteImages($book, $request->delete_images);
            }

            // Delete files if requested
            if ($request->has('delete_files')) {
                $this->deleteFiles($book, $request->delete_files);
            }

            // Get the updated book with relationships
            $result = $this->dbController->find($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Book updated successfully',
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
                'message' => 'Book not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update book',
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
                'message' => 'Book deleted successfully',
                'success' => $result
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Book not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete book',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function handleImageUploads(Book $book, array $images): void
    {
        foreach ($images as $index => $image) {
            if ($image->isValid()) {
                // Validate the image
                $validator = Validator::make(['image' => $image], [
                    'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                ]);

                if ($validator->fails()) {
                    continue; // Skip invalid images
                }

                // Store the file
                $path = $image->store('books/' . $book->id . '/images', 'public');

                // Create the book image record
                $book->images()->create([
                    'path' => $path,
                    'sort_order' => $index, // Use the array index as sort order
                ]);
            }
        }
    }


    private function handleFileUploads(Book $book, array $files): void
    {
        foreach ($files as $file) {
            if ($file->isValid()) {
                // Validate the file
                $validator = Validator::make(['file' => $file], [
                    'file' => 'file|mimes:pdf,doc,docx,txt|max:10240', // 10MB max
                ]);

                if ($validator->fails()) {
                    continue; // Skip invalid files
                }

                // Store the file
                $path = $file->store('books/' . $book->id . '/files', 'public');

                // Get the original file name
                $originalName = $file->getClientOriginalName();

                // Create the book file record
                $book->files()->create([
                    'path' => $path,
                    'name' => $originalName,
                    'type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }
    }


    private function deleteImages(Book $book, array $imageIds): void
    {
        $images = $book->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            // Delete the file
            Storage::disk('public')->delete($image->path);

            // Delete the record
            $image->delete();
        }
    }

    private function deleteFiles(Book $book, array $fileIds): void
    {
        $files = $book->files()->whereIn('id', $fileIds)->get();

        foreach ($files as $file) {
            // Delete the file
            Storage::disk('public')->delete($file->path);

            // Delete the record
            $file->delete();
        }
    }
}
