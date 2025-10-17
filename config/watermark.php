<?php
// config/watermark.php

return [
    'enabled' => env('WATERMARK_ENABLED', true),

    'image' => [
        'path' => env('WATERMARK_IMAGE_PATH', 'images/watermark/watermark.png'),
        'position' => env('WATERMARK_POSITION', 'bottom-right'),
        'offset_x' => env('WATERMARK_OFFSET_X', 20),
        'offset_y' => env('WATERMARK_OFFSET_Y', 20),
        'size_percentage' => env('WATERMARK_SIZE_PERCENTAGE', 0.10), // 10%
        'max_width' => env('WATERMARK_MAX_WIDTH', 1300),
        'min_width' => env('WATERMARK_MIN_WIDTH', 150),
    ],
    'style' => env('WATERMARK_STYLE', 'single'), // 'single' or 'tiled'

    'storage' => [
        'save_original' => env('WATERMARK_SAVE_ORIGINAL', true),
    ],
];
