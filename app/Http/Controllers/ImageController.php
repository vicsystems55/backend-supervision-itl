<?php
// app/Http/Controllers/ImageController.php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Installation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WatermarkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreImageRequest;

class ImageController extends Controller
{

    protected WatermarkService $watermarkService;

    public function __construct(WatermarkService $watermarkService)
    {
        $this->watermarkService = $watermarkService;
    }
    /**
     * Display a listing of images for a specific installation.
     */
    public function index(Request $request, Installation $installation): JsonResponse
    {
        $images = $installation->images()
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'description' => $image->description,
                    'url' => $image->url,
                    'file_name' => $image->file_name,
                    'file_size' => $image->formatted_size,
                    'mime_type' => $image->mime_type,
                    'latitude' => $image->latitude,
                    'longitude' => $image->longitude,
                    'created_at' => $image->created_at->toISOString(),
                    'uploaded_by' => $image->user->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $images,
            'message' => 'Images retrieved successfully.'
        ]);
    }

    /**
     * Store a newly created image with watermark
     */
public function store(StoreImageRequest $request, Installation $installation): JsonResponse
{
    try {
        DB::beginTransaction();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('images/installations', $fileName, 'public');

            // Store original image path
            $originalFilePath = $filePath;

            // Apply watermark if enabled and watermark exists
            $watermarkedFilePath = null;
            if (config('watermark.enabled') && $this->watermarkService->watermarkExists()) {
                try {
                    $watermarkOptions = [
                        'watermark_path' => config('watermark.image.path'),
                        'position' => config('watermark.image.position'),
                        'opacity' => config('watermark.image.opacity'),
                        'width' => config('watermark.image.width'),
                        'offset_x' => config('watermark.image.offset_x'),
                        'offset_y' => config('watermark.image.offset_y'),
                    ];

                    // Choose watermark style
                    if (config('watermark.style') === 'tiled') {
                        $watermarkedFilePath = $this->watermarkService->addTiledWatermark($filePath, $watermarkOptions);
                    } else {
                        $watermarkedFilePath = $this->watermarkService->addWatermark($filePath, $watermarkOptions);
                    }

                    // Use watermarked version as main file if configured
                    if (!config('watermark.storage.save_original')) {
                        Storage::disk('public')->delete($filePath);
                        $filePath = $watermarkedFilePath;
                    }

                } catch (\Exception $e) {
                    \Log::error('Watermark failed: ' . $e->getMessage());
                    // Continue with original image if watermark fails
                }
            }

            // Create image record
            $image = $installation->images()->create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'original_file_path' => $originalFilePath,
                'watermarked_file_path' => $watermarkedFilePath,
                'has_watermark' => !is_null($watermarkedFilePath),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $image->id,
                    'title' => $image->title,
                    'description' => $image->description,
                    'url' => $image->url,
                    'file_name' => $image->file_name,
                    'file_size' => $image->formatted_size,
                    'created_at' => $image->created_at->toISOString(),
                    'uploaded_by' => auth()->user()->name,
                    'has_watermark' => !is_null($watermarkedFilePath),
                ],
                'message' => 'Image uploaded successfully.'
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image file provided.'
        ], 400);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload image: ' . $e->getMessage()
        ], 500);
    }
}

// Add a method to check watermark status
public function watermarkStatus(): JsonResponse
{
    $status = $this->watermarkService->getWatermarkInfo();

    return response()->json([
        'success' => true,
        'data' => $status,
        'message' => $status['exists'] ? 'Watermark is configured' : 'Watermark image not found'
    ]);
}

    /**
     * Get original image (without watermark)
     */
    public function getOriginal(Installation $installation, Image $image)
    {
        if ($image->installation_id !== $installation->id) {
            abort(404, 'Image not found for this installation.');
        }

        if (!$image->original_file_path) {
            abort(404, 'Original image not available.');
        }

        $filePath = storage_path('app/public/' . $image->original_file_path);

        if (!file_exists($filePath)) {
            abort(404, 'Original image file not found.');
        }

        return response()->file($filePath);
    }

    /**
     * Get watermarked image
     */
    public function getWatermarked(Installation $installation, Image $image)
    {
        if ($image->installation_id !== $installation->id) {
            abort(404, 'Image not found for this installation.');
        }

        // If watermarked version exists, return it, otherwise return regular version
        $filePath = $image->watermarked_file_path ?: $image->file_path;
        $fullPath = storage_path('app/public/' . $filePath);

        if (!file_exists($fullPath)) {
            abort(404, 'Image file not found.');
        }

        return response()->file($fullPath);
    }

    /**
     * Display the specified image.
     */
    public function show(Installation $installation, Image $image): JsonResponse
    {
        // Verify the image belongs to the installation
        if ($image->installation_id !== $installation->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found for this installation.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $image->id,
                'title' => $image->title,
                'description' => $image->description,
                'url' => $image->url,
                'file_name' => $image->file_name,
                'file_size' => $image->formatted_size,
                'mime_type' => $image->mime_type,
                'latitude' => $image->latitude,
                'longitude' => $image->longitude,
                'created_at' => $image->created_at->toISOString(),
                'uploaded_by' => $image->user->name,
            ],
            'message' => 'Image retrieved successfully.'
        ]);
    }

    /**
     * Delete the specified image.
     */
    public function destroy(Installation $installation, Image $image): JsonResponse
    {
        try {
            // Verify the image belongs to the installation
            if ($image->installation_id !== $installation->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found for this installation.'
                ], 404);
            }

            // Delete file from storage
            Storage::disk('public')->delete($image->file_path);

            // Delete record from database
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified image.
     */
    public function download(Installation $installation, Image $image)
    {
        // Verify the image belongs to the installation
        if ($image->installation_id !== $installation->id) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found for this installation.'
            ], 404);
        }

        $filePath = storage_path('app/public/' . $image->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Image file not found.'
            ], 404);
        }

        return response()->download($filePath, $image->file_name);
    }
}
