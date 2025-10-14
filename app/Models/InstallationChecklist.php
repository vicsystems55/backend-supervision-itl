<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstallationChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'installation_id',
        'checklist_id',
        'status',
        'progress_percentage',
        'checklist_date',
        'installation_technician',
        'installation_company',
        'technician_signature',
        'health_center_signature',
        'health_center_name',
        'completion_date'
    ];

    protected $casts = [
        'checklist_date' => 'date',
        'completion_date' => 'date',
        'progress_percentage' => 'integer'
    ];

    /**
     * Get the installation that owns this checklist
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    /**
     * Get the checklist template used for this installation checklist
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Get all answers for this installation checklist
     */
    public function answers(): HasMany
    {
        return $this->hasMany(InstallationChecklistAnswer::class);
    }

    /**
     * Get the draft associated with this installation checklist
     */
    public function draft(): HasOne
    {
        return $this->hasOne(InstallationChecklistDraft::class, 'installation_id', 'installation_id')
            ->where('checklist_id', $this->checklist_id);
    }

    /**
     * Get answer for a specific question by question code
     */
    public function getAnswer(string $questionCode): ?string
    {
        $answer = $this->answers()
            ->whereHas('question', function ($query) use ($questionCode) {
                $query->where('question_code', $questionCode);
            })
            ->first();

        return $answer ? $answer->answer : null;
    }

    /**
     * Check if the checklist is in draft status
     */
    public function getIsDraftAttribute(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the checklist is submitted
     */
    public function getIsSubmittedAttribute(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if the checklist is verified
     */
    public function getIsVerifiedAttribute(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Scope to get only draft checklists
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only submitted checklists
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope to get only verified checklists
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * Scope to get checklists by installation ID
     */
    public function scopeByInstallation($query, $installationId)
    {
        return $query->where('installation_id', $installationId);
    }

    /**
     * Mark checklist as submitted
     */
    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'progress_percentage' => 100
        ]);
    }

    /**
     * Mark checklist as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['status' => 'verified']);
    }
}
