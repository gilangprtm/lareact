<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DB\BookController as DBBookController;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Books
 * @group Book Management
 */
class BookController extends Controller
{
    public function __construct(
        protected DBBookController $dbController
    ) {}

    /**
     * Get a paginated list of books with filtering options
     * 
     * Query parameters:
     * - search: Search term for book title, ISBN, or description
     * - page: Page number for pagination (default: 1)
     * - load: Number of items per page (default: 10)
     * - category_id: Filter by category ID
     * - publisher_id: Filter by publisher ID
     * - status: Filter by book status (available, out_of_stock, coming_soon)
     * - min_price: Filter by minimum price
     * - max_price: Filter by maximum price
     * - language: Filter by language
     * - is_featured: Filter featured books (true/false)
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

    /**
     * Get book details by ID
     */
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

    /**
     * Create a new book
     * 
     * Required fields:
     * - title: Book title
     * - isbn: Book ISBN
     * - category_id: Category ID
     * - publisher_id: Publisher ID
     * - publish_date: Publication date (YYYY-MM-DD)
     * - pages: Number of pages
     * - description: Book description
     * - status: Book status (available, out_of_stock, coming_soon)
     * - price: Book price
     * - language: Book language
     * - author_ids: Array of author IDs
     * 
     * Optional fields:
     * - is_featured: Whether the book is featured (true/false)
     * - images[]: Multiple book cover/promotional images (file uploads)
     * - files[]: Multiple book-related files (e.g., sample chapters) (file uploads)
     * 
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Update an existing book
     * 
     * Optional fields:
     * - title: Book title
     * - isbn: Book ISBN
     * - category_id: Category ID
     * - publisher_id: Publisher ID
     * - publish_date: Publication date (YYYY-MM-DD)
     * - pages: Number of pages
     * - description: Book description
     * - status: Book status (available, out_of_stock, coming_soon)
     * - price: Book price
     * - is_featured: Whether the book is featured (true/false)
     * - language: Book language
     * - author_ids: Array of author IDs
     * - images[]: Multiple book cover/promotional images (file uploads)
     * - files[]: Multiple book-related files (e.g., sample chapters) (file uploads)
     * - delete_images[]: IDs of images to delete
     * - delete_files[]: IDs of files to delete
     */
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

    /**
     * Delete a book
     */
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

    /**
     * Handle uploading multiple images for a book
     */
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

    /**
     * Handle uploading multiple files for a book
     */
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

    /**
     * Delete book images by ID
     */
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

    /**
     * Delete book files by ID
     */
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
