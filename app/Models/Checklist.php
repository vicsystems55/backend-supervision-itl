<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'version',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get all sections for this checklist
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ChecklistSection::class)->orderBy('order');
    }

    /**
     * Get all questions for this checklist (through sections)
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(
            ChecklistQuestion::class,
            ChecklistSection::class,
            'checklist_id', // Foreign key on checklist_sections table
            'checklist_section_id', // Foreign key on checklist_questions table
            'id', // Local key on checklists table
            'id' // Local key on checklist_sections table
        );
    }

    /**
     * Get all installation checklists for this checklist template
     */
    public function installationChecklists(): HasMany
    {
        return $this->hasMany(InstallationChecklist::class);
    }

    /**
     * Scope to get only active checklists
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the latest version of this checklist
     */
    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    /**
     * Get total questions count for this checklist
     */
    public function getTotalQuestionsCountAttribute(): int
    {
        return $this->questions()->count();
    }

    /**
     * Get required questions count for this checklist
     */
    public function getRequiredQuestionsCountAttribute(): int
    {
        return $this->questions()->where('required', true)->count();
    }
}
