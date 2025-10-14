<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    /**
     * Display a listing of the checklists.
     */
    public function index(): JsonResponse
    {
        $checklists = Checklist::with(['sections.questions'])
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $checklists,
            'message' => 'Checklists retrieved successfully.'
        ]);
    }

    /**
     * Display the specified checklist.
     */
    public function show($id): JsonResponse
    {
        $checklist = Checklist::with(['sections.questions' => function ($query) {
            $query->ordered();
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $checklist,
            'message' => 'Checklist retrieved successfully.'
        ]);
    }

    /**
     * Get the active checklist structure.
     */
    public function getActiveChecklist(): JsonResponse
    {
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
            'data' => $checklist,
            'message' => 'Active checklist retrieved successfully.'
        ]);
    }

    /**
     * Store a newly created checklist.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $checklist = Checklist::create($validated);

        return response()->json([
            'success' => true,
            'data' => $checklist,
            'message' => 'Checklist created successfully.'
        ], 201);
    }

    /**
     * Update the specified checklist.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $checklist = Checklist::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'version' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $checklist->update($validated);

        return response()->json([
            'success' => true,
            'data' => $checklist,
            'message' => 'Checklist updated successfully.'
        ]);
    }

    /**
     * Remove the specified checklist.
     */
    public function destroy($id): JsonResponse
    {
        $checklist = Checklist::findOrFail($id);
        $checklist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checklist deleted successfully.'
        ]);
    }
}
