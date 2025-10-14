<?php

namespace App\Http\Controllers;

use App\Models\InstallationChecklistDraft;
use App\Models\Installation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallationChecklistDraftController extends Controller
{
    /**
     * Display a listing of drafts for an installation.
     */
    public function index($installationId): JsonResponse
    {
        $installation = Installation::findOrFail($installationId);

        $drafts = InstallationChecklistDraft::with('checklist')
            ->where('installation_id', $installationId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drafts,
            'message' => 'Drafts retrieved successfully.'
        ]);
    }

    /**
     * Display the specified draft.
     */
    public function show($installationId, $draftId): JsonResponse
    {
        $draft = InstallationChecklistDraft::with(['checklist', 'installation'])
            ->where('installation_id', $installationId)
            ->findOrFail($draftId);

        return response()->json([
            'success' => true,
            'data' => $draft,
            'message' => 'Draft retrieved successfully.'
        ]);
    }

    /**
     * Remove the specified draft.
     */
    public function destroy($installationId, $draftId): JsonResponse
    {
        $draft = InstallationChecklistDraft::where('installation_id', $installationId)
            ->findOrFail($draftId);

        $draft->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully.'
        ]);
    }

    /**
     * Clean up stale drafts (older than 7 days)
     */
    public function cleanupStaleDrafts(): JsonResponse
    {
        $staleDrafts = InstallationChecklistDraft::stale()->get();
        $deletedCount = InstallationChecklistDraft::stale()->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $deletedCount,
                'stale_drafts' => $staleDrafts
            ],
            'message' => 'Stale drafts cleaned up successfully.'
        ]);
    }

    /**
     * Get draft statistics
     */
    public function getStats(): JsonResponse
    {
        $totalDrafts = InstallationChecklistDraft::count();
        $recentDrafts = InstallationChecklistDraft::recent()->count();
        $staleDrafts = InstallationChecklistDraft::stale()->count();
        $averageProgress = InstallationChecklistDraft::avg('progress_percentage');

        return response()->json([
            'success' => true,
            'data' => [
                'total_drafts' => $totalDrafts,
                'recent_drafts' => $recentDrafts,
                'stale_drafts' => $staleDrafts,
                'average_progress' => round($averageProgress, 2),
            ],
            'message' => 'Draft statistics retrieved successfully.'
        ]);
    }
}
