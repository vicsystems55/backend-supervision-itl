<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Checklist;
use App\Models\InstallationChecklist;
use App\Models\InstallationChecklistDraft;
use App\Models\InstallationChecklistAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstallationChecklistController extends Controller
{
    /**
     * Get checklist structure for an installation
     */
    public function getChecklistStructure($installationId): JsonResponse
    {
        $installation = Installation::findOrFail($installationId);

        $checklist = Checklist::with(['sections' => function ($query) {
            $query->ordered()->with(['questions' => function ($query) {
                $query->ordered();
            }]);
        }])
        ->active()
        ->first();

        if (!$checklist) {
            return response()->json([
                'success' => false,
                'message' => 'No active checklist found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'checklist' => $checklist,
                'installation' => $installation
            ],
            'message' => 'Checklist structure retrieved successfully.'
        ]);
    }

    /**
     * Get draft for an installation checklist
     */
    public function getDraft($installationId): JsonResponse
    {
        $installation = Installation::findOrFail($installationId);
        $checklist = Checklist::active()->first();

        if (!$checklist) {
            return response()->json([
                'success' => false,
                'message' => 'No active checklist found.'
            ], 404);
        }

        $draft = InstallationChecklistDraft::where('installation_id', $installationId)
            ->where('checklist_id', $checklist->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $draft,
            'message' => 'Draft loaded successfully.'
        ]);
    }

    /**
     * Save draft for an installation checklist
     */
    public function saveDraft(Request $request, $installationId): JsonResponse
    {
        $request->validate([
            'form_data' => 'required|array',
            'progress_percentage' => 'required|integer|min:0|max:100',
            'section' => 'nullable|string'
        ]);

        $installation = Installation::findOrFail($installationId);
        $checklist = Checklist::active()->first();

        if (!$checklist) {
            return response()->json([
                'success' => false,
                'message' => 'No active checklist found.'
            ], 404);
        }

        $draft = InstallationChecklistDraft::updateOrCreate(
            [
                'installation_id' => $installationId,
                'checklist_id' => $checklist->id
            ],
            [
                'form_data' => $request->form_data,
                'progress_percentage' => $request->progress_percentage,
                'last_saved_section' => $request->section,
                'last_saved_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $draft,
            'message' => 'Draft saved successfully.'
        ]);
    }

    /**
     * Submit completed checklist
     */
    public function submitChecklist(Request $request, $installationId): JsonResponse
    {
        $request->validate([
            'form_data' => 'required|array',
            'checklist_date' => 'required|date',
            'installation_technician' => 'required|string|max:255',
            'technician_signature' => 'required|string|max:255',
            'health_center_signature' => 'required|string|max:255',
            'health_center_name' => 'required|string|max:255',
            'completion_date' => 'required|date',
        ]);

        $installation = Installation::findOrFail($installationId);
        $checklist = Checklist::active()->first();

        if (!$checklist) {
            return response()->json([
                'success' => false,
                'message' => 'No active checklist found.'
            ], 404);
        }

        try {
            DB::transaction(function () use ($request, $installationId, $checklist, $installation) {
                // Create main checklist record
                $installationChecklist = InstallationChecklist::create([
                    'installation_id' => $installationId,
                    'checklist_id' => $checklist->id,
                    'status' => 'submitted',
                    'progress_percentage' => 100,
                    'checklist_date' => $request->checklist_date,
                    'installation_technician' => $request->installation_technician,
                    'installation_company' => $request->form_data['installationCompany'] ?? 'Inter-Trade Ltd.',
                    'technician_signature' => $request->technician_signature,
                    'health_center_signature' => $request->health_center_signature,
                    'health_center_name' => $request->health_center_name,
                    'completion_date' => $request->completion_date,
                ]);

                // Save answers for each question
                $questions = $checklist->questions;
                foreach ($questions as $question) {
                    $answerValue = $request->form_data[$question->question_code] ?? null;

                    if ($answerValue !== null && $answerValue !== '') {
                        InstallationChecklistAnswer::create([
                            'installation_checklist_id' => $installationChecklist->id,
                            'checklist_question_id' => $question->id,
                            'answer' => is_array($answerValue) ? json_encode($answerValue) : $answerValue
                        ]);
                    }
                }

                // Delete draft after successful submission
                InstallationChecklistDraft::where('installation_id', $installationId)
                    ->where('checklist_id', $checklist->id)
                    ->delete();

                // Update installation verification status if recommendation is PASS
                $recommendation = $request->form_data['recommendation'] ?? null;
                if ($recommendation === 'PASS') {
                    $installation->update(['verified_by_health_officer' => true]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Checklist submitted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit checklist: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete draft for an installation checklist
     */
    public function deleteDraft($installationId): JsonResponse
    {
        $installation = Installation::findOrFail($installationId);
        $checklist = Checklist::active()->first();

        if (!$checklist) {
            return response()->json([
                'success' => false,
                'message' => 'No active checklist found.'
            ], 404);
        }

        InstallationChecklistDraft::where('installation_id', $installationId)
            ->where('checklist_id', $checklist->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully.'
        ]);
    }

    /**
     * Get submitted checklists for an installation
     */
    public function getInstallationChecklists($installationId): JsonResponse
    {
        $installation = Installation::findOrFail($installationId);

        $checklists = InstallationChecklist::with(['checklist', 'answers.question'])
            ->where('installation_id', $installationId)
            ->submitted()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checklists,
            'message' => 'Installation checklists retrieved successfully.'
        ]);
    }
}
