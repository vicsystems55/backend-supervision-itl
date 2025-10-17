<?php
// app/Models/Image.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory;

    // Add to $fillable array:
    protected $fillable = [
        'installation_id',
        'user_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'latitude',
        'longitude',
        'original_file_path', // Add this
        'watermarked_file_path', // Add this
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the installation that owns the image.
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    /**
     * Get the user that uploaded the image.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->watermarked_file_path);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // Add accessor for watermarked URL
    public function getWatermarkedUrlAttribute(): ?string
    {
        return $this->watermarked_file_path ? asset('storage/' . $this->watermarked_file_path) : null;
    }

    public function getOriginalUrlAttribute(): ?string
    {
        return $this->original_file_path ? asset('storage/' . $this->original_file_path) : null;
    }
}
