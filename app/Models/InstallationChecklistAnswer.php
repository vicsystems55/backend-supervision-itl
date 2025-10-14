<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationChecklistAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_checklist_id',
        'checklist_question_id',
        'answer'
    ];

    /**
     * Get the installation checklist that owns this answer
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(InstallationChecklist::class, 'installation_checklist_id');
    }

    /**
     * Get the question that this answer belongs to
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ChecklistQuestion::class, 'checklist_question_id');
    }

    /**
     * Get the answer value with proper casting based on question type
     */
    public function getValueAttribute()
    {
        if ($this->question->is_select_type && $this->answer) {
            return $this->answer;
        }

        if ($this->question->type === 'number' && $this->answer !== null) {
            return is_numeric($this->answer) ? (float) $this->answer : null;
        }

        if ($this->question->type === 'yes_no') {
            return $this->answer === 'yes';
        }

        if ($this->question->type === 'date' && $this->answer) {
            return $this->answer; // Return as string, frontend can handle date formatting
        }

        return $this->answer;
    }

    /**
     * Set the answer value with proper formatting
     */
    public function setValueAttribute($value): void
    {
        if ($this->question && $this->question->type === 'yes_no') {
            $this->attributes['answer'] = $value ? 'yes' : 'no';
        } elseif (is_array($value)) {
            $this->attributes['answer'] = json_encode($value);
        } else {
            $this->attributes['answer'] = $value;
        }
    }

    /**
     * Check if the answer is considered "filled" (not empty)
     */
    public function getIsFilledAttribute(): bool
    {
        return !empty($this->answer) && $this->answer !== '';
    }

    /**
     * Scope to get answers for a specific question code
     */
    public function scopeForQuestionCode($query, $questionCode)
    {
        return $query->whereHas('question', function ($q) use ($questionCode) {
            $q->where('question_code', $questionCode);
        });
    }

    /**
     * Scope to get answers for specific installation checklist
     */
    public function scopeForChecklist($query, $installationChecklistId)
    {
        return $query->where('installation_checklist_id', $installationChecklistId);
    }
}
