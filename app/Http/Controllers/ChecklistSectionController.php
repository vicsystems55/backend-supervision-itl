<?php

namespace App\Http\Controllers;

use App\Models\ChecklistSection;
use App\Models\Checklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistSectionController extends Controller
{
    /**
     * Display a listing of sections for a checklist.
     */
    public function index($checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        $sections = ChecklistSection::with(['questions' => function ($query) {
            $query->ordered();
        }])
        ->where('checklist_id', $checklistId)
        ->ordered()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $sections,
            'message' => 'Checklist sections retrieved successfully.'
        ]);
    }

    /**
     * Store a newly created section for a checklist.
     */
    public function store(Request $request, $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
        ]);

        $validated['checklist_id'] = $checklistId;

        $section = ChecklistSection::create($validated);

        return response()->json([
            'success' => true,
            'data' => $section->load('questions'),
            'message' => 'Checklist section created successfully.'
        ], 201);
    }

    /**
     * Display the specified section.
     */
    public function show($checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::with(['questions' => function ($query) {
            $query->ordered();
        }])
        ->where('checklist_id', $checklistId)
        ->findOrFail($sectionId);

        return response()->json([
            'success' => true,
            'data' => $section,
            'message' => 'Checklist section retrieved successfully.'
        ]);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, $checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::where('checklist_id', $checklistId)
            ->findOrFail($sectionId);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|integer|min:0',
        ]);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'data' => $section->load('questions'),
            'message' => 'Checklist section updated successfully.'
        ]);
    }

    /**
     * Remove the specified section.
     */
    public function destroy($checklistId, $sectionId): JsonResponse
    {
        $section = ChecklistSection::where('checklist_id', $checklistId)
            ->findOrFail($sectionId);

        // Check if section has questions
        if ($section->questions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete section that contains questions. Please delete the questions first.'
            ], 422);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist section deleted successfully.'
        ]);
    }

    /**
     * Reorder sections for a checklist
     */
    public function reorder(Request $request, $checklistId): JsonResponse
    {
        $checklist = Checklist::findOrFail($checklistId);

        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:checklist_sections,id',
            'sections.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->sections as $sectionData) {
            ChecklistSection::where('id', $sectionData['id'])
                ->where('checklist_id', $checklistId)
                ->update(['order' => $sectionData['order']]);
        }

        $sections = ChecklistSection::where('checklist_id', $checklistId)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections,
            'message' => 'Sections reordered successfully.'
        ]);
    }
}
