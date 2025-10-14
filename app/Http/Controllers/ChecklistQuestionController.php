<?php

namespace App\Http\Controllers;

use App\Models\ChecklistQuestion;
use App\Models\ChecklistSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistQuestionController extends Controller
{
    /**
     * Display a listing of questions for a section.
     */
    public function index($checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::where('checklist_id', $checklistId)
            ->findOrFail($sectionId);

        $questions = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
            'message' => 'Checklist questions retrieved successfully.'
        ]);
    }

    /**
     * Store a newly created question for a section.
     */
    public function store(Request $request, $checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::where('checklist_id', $checklistId)
            ->findOrFail($sectionId);

        $validated = $request->validate([
            'question_code' => 'required|string|max:100|unique:checklist_questions,question_code',
            'question_text' => 'required|string',
            'type' => 'required|in:yes_no,text,number,select,textarea,date,signature',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'validation_rules' => 'nullable|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'order' => 'required|integer|min:0',
        ]);

        $validated['checklist_section_id'] = $sectionId;

        $question = ChecklistQuestion::create($validated);

        return response()->json([
            'success' => true,
            'data' => $question,
            'message' => 'Checklist question created successfully.'
        ], 201);
    }

    /**
     * Display the specified question.
     */
    public function show($checklistId, $sectionId, $questionId): JsonResponse
    {
        $question = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->findOrFail($questionId);

        return response()->json([
            'success' => true,
            'data' => $question,
            'message' => 'Checklist question retrieved successfully.'
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, $checklistId, $sectionId, $questionId): JsonResponse
    {
        $question = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->findOrFail($questionId);

        $validated = $request->validate([
            'question_code' => 'sometimes|string|max:100|unique:checklist_questions,question_code,' . $questionId,
            'question_text' => 'sometimes|string',
            'type' => 'sometimes|in:yes_no,text,number,select,textarea,date,signature',
            'options' => 'nullable|array',
            'required' => 'sometimes|boolean',
            'validation_rules' => 'nullable|string|max:255',
            'placeholder' => 'nullable|string|max:255',
            'order' => 'sometimes|integer|min:0',
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'data' => $question,
            'message' => 'Checklist question updated successfully.'
        ]);
    }

    /**
     * Remove the specified question.
     */
    public function destroy($checklistId, $sectionId, $questionId): JsonResponse
    {
        $question = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->findOrFail($questionId);

        // Check if question has answers
        if ($question->answers()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete question that has answers. Please delete the answers first.'
            ], 422);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist question deleted successfully.'
        ]);
    }

    /**
     * Reorder questions for a section
     */
    public function reorder(Request $request, $checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::where('checklist_id', $checklistId)
            ->findOrFail($sectionId);

        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:checklist_questions,id',
            'questions.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->questions as $questionData) {
            ChecklistQuestion::where('id', $questionData['id'])
                ->where('checklist_section_id', $sectionId)
                ->update(['order' => $questionData['order']]);
        }

        $questions = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
            'message' => 'Questions reordered successfully.'
        ]);
    }

    /**
     * Get validation rules for a question
     */
    public function getValidationRules($checklistId, $sectionId, $questionId): JsonResponse
    {
        $question = ChecklistQuestion::where('checklist_section_id', $sectionId)
            ->findOrFail($questionId);

        return response()->json([
            'success' => true,
            'data' => [
                'validation_rules' => $question->validation_rules_array,
                'question_type' => $question->type,
                'is_required' => $question->required
            ],
            'message' => 'Validation rules retrieved successfully.'
        ]);
    }
}
