<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationChecklistDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'checklist_id',
        'form_data',
        'progress_percentage',
        'last_saved_section',
        'last_saved_at'
    ];

    protected $casts = [
        'form_data' => 'array',
        'last_saved_at' => 'datetime',
        'progress_percentage' => 'integer'
    ];

    /**
     * Get the installation that owns this draft
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    /**
     * Get the checklist template used for this draft
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Check if the draft is recent (saved within the last 24 hours)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->last_saved_at->gt(now()->subDay());
    }

    /**
     * Check if the draft is stale (not saved for over 7 days)
     */
    public function getIsStaleAttribute(): bool
    {
        return $this->last_saved_at->lt(now()->subDays(7));
    }

    /**
     * Get the time since last save in human readable format
     */
    public function getTimeSinceLastSaveAttribute(): string
    {
        return $this->last_saved_at->diffForHumans();
    }

    /**
     * Update the draft with new form data
     */
    public function updateDraft(array $formData, int $progress, ?string $section = null): void
    {
        $this->update([
            'form_data' => $formData,
            'progress_percentage' => $progress,
            'last_saved_section' => $section,
            'last_saved_at' => now()
        ]);
    }

    /**
     * Scope to get only recent drafts (within 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('last_saved_at', '>', now()->subDay());
    }

    /**
     * Scope to get stale drafts (older than 7 days)
     */
    public function scopeStale($query)
    {
        return $query->where('last_saved_at', '<', now()->subDays(7));
    }

    /**
     * Scope to get drafts for a specific installation
     */
    public function scopeForInstallation($query, $installationId)
    {
        return $query->where('installation_id', $installationId);
    }

    /**
     * Scope to get drafts for a specific checklist
     */
    public function scopeForChecklist($query, $checklistId)
    {
        return $query->where('checklist_id', $checklistId);
    }

    /**
     * Get a specific value from form data
     */
    public function getFormValue(string $key, $default = null)
    {
        return $this->form_data[$key] ?? $default;
    }

    /**
     * Check if a specific section has been started in the draft
     */
    public function isSectionStarted(string $section): bool
    {
        // Check if any questions in this section have answers in the form_data
        $sectionQuestions = $this->checklist->questions()
            ->whereHas('section', function ($query) use ($section) {
                $query->where('title', 'LIKE', "%{$section}%");
            })
            ->pluck('question_code')
            ->toArray();

        foreach ($sectionQuestions as $questionCode) {
            if (isset($this->form_data[$questionCode]) && !empty($this->form_data[$questionCode])) {
                return true;
            }
        }

        return false;
    }
}
