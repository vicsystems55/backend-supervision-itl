<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_section_id',
        'question_code',
        'question_text',
        'type',
        'options',
        'required',
        'validation_rules',
        'placeholder',
        'order'
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean'
    ];

    /**
     * Get the section that owns this question
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ChecklistSection::class, 'checklist_section_id');
    }

    /**
     * Get all answers for this question
     */
    public function answers(): HasMany
    {
        return $this->hasMany(InstallationChecklistAnswer::class);
    }

    /**
     * Helper method to get validation rules as array
     */
    public function getValidationRulesArrayAttribute(): array
    {
        if (!$this->validation_rules) {
            return $this->required ? ['required'] : ['nullable'];
        }

        return explode('|', $this->validation_rules);
    }

    /**
     * Check if this question is of yes/no type
     */
    public function getIsYesNoTypeAttribute(): bool
    {
        return $this->type === 'yes_no';
    }

    /**
     * Check if this question is of select type
     */
    public function getIsSelectTypeAttribute(): bool
    {
        return $this->type === 'select';
    }

    /**
     * Check if this question is of text type
     */
    public function getIsTextTypeAttribute(): bool
    {
        return in_array($this->type, ['text', 'textarea']);
    }

    /**
     * Scope to order questions by their order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to get only required questions
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    /**
     * Get the full question path (for debugging/display)
     */
    public function getFullPathAttribute(): string
    {
        return $this->section->checklist->name . ' -> ' .
               $this->section->title . ' -> ' .
               $this->question_text;
    }
}
