<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'title',
        'description',
        'order'
    ];

    /**
     * Get the checklist that owns this section
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get all questions for this section
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ChecklistQuestion::class)->orderBy('order');
    }

    /**
     * Scope to order sections by their order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get questions count for this section
     */
    public function getQuestionsCountAttribute(): int
    {
        return $this->questions()->count();
    }

    /**
     * Get required questions count for this section
     */
    public function getRequiredQuestionsCountAttribute(): int
    {
        return $this->questions()->where('required', true)->count();
    }
}
