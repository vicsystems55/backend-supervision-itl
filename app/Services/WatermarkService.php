<?php
// app/Services/WatermarkService.php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class WatermarkService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Add watermark with dynamic sizing (10% of uploaded image)
     */
    public function addWatermark(string $imagePath, array $options = []): string
    {
        $defaultOptions = [
            'watermark_path' => 'images/watermark/watermark.png',
            'position' => 'bottom-right',
            'offset_x' => 20,
            'offset_y' => 20,
            'size_percentage' => 0.5, // 10% of the uploaded image
            'max_width' => 1300, // Maximum width to prevent huge watermarks
            'min_width' => 150,  // Minimum width to prevent tiny watermarks
        ];

        $options = array_merge($defaultOptions, $options);

        // Fix path if it's absolute
        $options['watermark_path'] = $this->fixWatermarkPath($options['watermark_path']);

        try {
            // Validate files exist
            if (!Storage::disk('public')->exists($imagePath)) {
                throw new \Exception("Source image not found: {$imagePath}");
            }

            if (!Storage::disk('public')->exists($options['watermark_path'])) {
                throw new \Exception("Watermark image not found: {$options['watermark_path']}");
            }

            // Read images
            $image = $this->imageManager->read(Storage::disk('public')->get($imagePath));
            $watermark = $this->imageManager->read(Storage::disk('public')->get($options['watermark_path']));

            // Calculate optimal watermark size
            $targetSize = $this->calculateWatermarkSize($image, $watermark, $options);

            // Resize watermark proportionally
            $watermark->scaleDown($targetSize['width'], $targetSize['height']);

            // Place watermark
            $image->place(
                $watermark,
                $options['position'],
                $options['offset_x'],
                $options['offset_y']
            );

            // Save watermarked image
            $watermarkedPath = $this->generateWatermarkedPath($imagePath);
            $this->ensureDirectoryExists($watermarkedPath);

            Storage::disk('public')->put($watermarkedPath, $image->encode());

            return $watermarkedPath;

        } catch (\Exception $e) {
            throw new \Exception("Watermark failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate optimal watermark size (10% of uploaded image)
     */
    protected function calculateWatermarkSize($mainImage, $watermarkImage, array $options): array
    {
        $mainWidth = $mainImage->width();
        $mainHeight = $mainImage->height();

        $watermarkWidth = $watermarkImage->width();
        $watermarkHeight = $watermarkImage->height();
        $watermarkAspectRatio = $watermarkWidth / $watermarkHeight;

        // Calculate target size based on percentage
        $targetWidth = (int) ($mainWidth * $options['size_percentage']);
        $targetHeight = (int) ($targetWidth / $watermarkAspectRatio);

        // Apply constraints
        $targetWidth = max($options['min_width'], min($targetWidth, $options['max_width']));
        $targetHeight = max(
            (int) ($options['min_width'] / $watermarkAspectRatio),
            min($targetHeight, (int) ($options['max_width'] / $watermarkAspectRatio))
        );

        // Ensure we don't exceed original watermark dimensions
        $targetWidth = min($targetWidth, $watermarkWidth);
        $targetHeight = min($targetHeight, $watermarkHeight);

        return [
            'width' => $targetWidth,
            'height' => $targetHeight,
            'original_main_width' => $mainWidth,
            'original_main_height' => $mainHeight,
            'original_watermark_width' => $watermarkWidth,
            'original_watermark_height' => $watermarkHeight,
            'calculated_percentage' => ($targetWidth / $mainWidth) * 100,
        ];
    }

    /**
     * Alternative method with different sizing strategies
     */
    public function addProportionalWatermark(string $imagePath, array $options = []): string
    {
        $defaultOptions = [
            'watermark_path' => 'images/watermark/watermark.png',
            'position' => 'bottom-right',
            'offset_x' => 20,
            'offset_y' => 20,
            'strategy' => 'width', // 'width', 'height', or 'diagonal'
            'percentage' => 0.10, // 10%
        ];

        $options = array_merge($defaultOptions, $options);
        $options['watermark_path'] = $this->fixWatermarkPath($options['watermark_path']);

        try {
            if (!Storage::disk('public')->exists($imagePath) || !Storage::disk('public')->exists($options['watermark_path'])) {
                throw new \Exception("Required images not found");
            }

            $image = $this->imageManager->read(Storage::disk('public')->get($imagePath));
            $watermark = $this->imageManager->read(Storage::disk('public')->get($options['watermark_path']));

            // Calculate size based on strategy
            $targetSize = $this->calculateSizeByStrategy($image, $watermark, $options);
            $watermark->scaleDown($targetSize['width'], $targetSize['height']);

            $image->place(
                $watermark,
                $options['position'],
                $options['offset_x'],
                $options['offset_y']
            );

            $watermarkedPath = $this->generateWatermarkedPath($imagePath);
            $this->ensureDirectoryExists($watermarkedPath);

            Storage::disk('public')->put($watermarkedPath, $image->encode());

            return $watermarkedPath;

        } catch (\Exception $e) {
            throw new \Exception("Proportional watermark failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate size based on different strategies
     */
    protected function calculateSizeByStrategy($mainImage, $watermarkImage, array $options): array
    {
        $mainWidth = $mainImage->width();
        $mainHeight = $mainImage->height();
        $watermarkWidth = $watermarkImage->width();
        $watermarkHeight = $watermarkImage->height();
        $aspectRatio = $watermarkWidth / $watermarkHeight;

        switch ($options['strategy']) {
            case 'width':
                $targetWidth = (int) ($mainWidth * $options['percentage']);
                $targetHeight = (int) ($targetWidth / $aspectRatio);
                break;

            case 'height':
                $targetHeight = (int) ($mainHeight * $options['percentage']);
                $targetWidth = (int) ($targetHeight * $aspectRatio);
                break;

            case 'diagonal':
                $mainDiagonal = sqrt(pow($mainWidth, 2) + pow($mainHeight, 2));
                $watermarkDiagonal = sqrt(pow($watermarkWidth, 2) + pow($watermarkHeight, 2));
                $scale = ($mainDiagonal * $options['percentage']) / $watermarkDiagonal;
                $targetWidth = (int) ($watermarkWidth * $scale);
                $targetHeight = (int) ($watermarkHeight * $scale);
                break;

            default:
                $targetWidth = (int) ($mainWidth * $options['percentage']);
                $targetHeight = (int) ($targetWidth / $aspectRatio);
        }

        // Apply bounds
        $targetWidth = max(30, min($targetWidth, min($mainWidth, 400)));
        $targetHeight = max(20, min($targetHeight, min($mainHeight, 300)));

        return [
            'width' => $targetWidth,
            'height' => $targetHeight,
            'strategy' => $options['strategy'],
            'percentage' => $options['percentage'] * 100,
        ];
    }

    /**
     * Get watermark size info for debugging
     */
    public function getSizeCalculation(string $imagePath): array
    {
        $options = [
            'watermark_path' => 'images/watermark/watermark.png',
            'size_percentage' => 0.50,
            'max_width' => 1300,
            'min_width' => 150,
        ];

        $options['watermark_path'] = $this->fixWatermarkPath($options['watermark_path']);

        try {
            $image = $this->imageManager->read(Storage::disk('public')->get($imagePath));
            $watermark = $this->imageManager->read(Storage::disk('public')->get($options['watermark_path']));

            $sizeInfo = $this->calculateWatermarkSize($image, $watermark, $options);

            return [
                'success' => true,
                'main_image' => [
                    'width' => $image->width(),
                    'height' => $image->height(),
                ],
                'watermark_original' => [
                    'width' => $watermark->width(),
                    'height' => $watermark->height(),
                ],
                'watermark_resized' => [
                    'width' => $sizeInfo['width'],
                    'height' => $sizeInfo['height'],
                ],
                'calculation' => $sizeInfo,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Fix watermark path if it's absolute
     */
    protected function fixWatermarkPath(string $path): string
    {
        if (str_contains($path, 'storage\\app\\public\\') || str_contains($path, 'storage/app/public/')) {
            return 'images/watermark/watermark.png';
        }
        return $path;
    }

    /**
     * Generate path for watermarked image
     */
    protected function generateWatermarkedPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/watermarked/' . $pathInfo['filename'] . '_watermarked.' . $pathInfo['extension'];
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Check if watermark exists
     */
    public function watermarkExists(): bool
    {
        $path = $this->fixWatermarkPath(config('watermark.image.path', 'images/watermark/watermark.png'));
        return Storage::disk('public')->exists($path);
    }

    /**
     * Get watermark info
     */
    public function getWatermarkInfo(): array
    {
        $path = $this->fixWatermarkPath(config('watermark.image.path', 'images/watermark/watermark.png'));

        if (!Storage::disk('public')->exists($path)) {
            return ['exists' => false];
        }

        try {
            $watermark = $this->imageManager->read(Storage::disk('public')->get($path));

            return [
                'exists' => true,
                'width' => $watermark->width(),
                'height' => $watermark->height(),
                'size' => Storage::disk('public')->size($path),
                'url' => asset('storage/' . $path),
                'aspect_ratio' => round($watermark->width() / $watermark->height(), 2),
            ];
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
