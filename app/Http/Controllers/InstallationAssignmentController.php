<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Installation;
use Illuminate\Http\Request;
use App\Models\InstallationSite;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InstallationAssignment;

class InstallationAssignmentController extends Controller
{
    public function index()
    {
        $assignments = InstallationAssignment::with(['user.roles', 'installationSite'])->get();

        return response()->json([
            'data' => $assignments
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'installation_site_id' => 'required|exists:installation_sites,id',
        ]);

        $user = User::find($request->user_id);

        if (!$user->isTechnicianTeam()) {
            return response()->json([
                'error' => 'User must be a Technician Lead or Technician Assistant'
            ], 422);
        }

        $existingAssignment = InstallationAssignment::where('user_id', $request->user_id)
            ->where('installation_site_id', $request->installation_site_id)
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'error' => 'Technician already assigned to this site'
            ], 422);
        }

        $assignment = InstallationAssignment::create($request->all());

        return response()->json([
            'message' => 'Assignment created successfully',
            'data' => $assignment->load(['user', 'installationSite'])
        ], 201);
    }

    public function show(InstallationAssignment $installationAssignment)
    {
        return response()->json([
            'data' => $installationAssignment->load(['user.roles', 'installationSite'])
        ]);
    }

    public function update(Request $request, InstallationAssignment $installationAssignment)
    {
        $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'installation_site_id' => 'sometimes|required|exists:installation_sites,id',
            'status' => 'sometimes|required|in:assigned,in_progress,completed,cancelled'
        ]);

        if ($request->has('user_id')) {
            $user = User::find($request->user_id);
            if (!$user->isTechnicianTeam()) {
                return response()->json([
                    'error' => 'User must be a Technician Lead or Technician Assistant'
                ], 422);
            }

            $existingAssignment = InstallationAssignment::where('user_id', $request->user_id)
                ->where('installation_site_id', $request->installation_site_id ?? $installationAssignment->installation_site_id)
                ->where('id', '!=', $installationAssignment->id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'error' => 'Technician already assigned to this site'
                ], 422);
            }
        }

        $installationAssignment->update($request->all());

        return response()->json([
            'message' => 'Assignment updated successfully',
            'data' => $installationAssignment->load(['user', 'installationSite'])
        ]);
    }

    public function destroy(InstallationAssignment $installationAssignment)
    {
        $installationAssignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully'
        ]);
    }

    // Get assignments for a specific technician
    public function getTechnicianAssignments(User $user)
    {
        if (!$user->isTechnicianTeam()) {
            return response()->json([
                'error' => 'User is not a technician'
            ], 422);
        }

        $assignments = $user->installationAssignments()->with('installationSite')->get();

        return response()->json([
            'data' => $assignments
        ]);
    }

    // Get technicians assigned to a site
    public function getSiteAssignments(Installation $installation)
    {
        $assignments = $installation->installationAssignments()->with('user.roles')->get();

        return response()->json([
            'data' => $assignments
        ]);
    }



     public function bulkAssign(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'installation_ids' => 'required|array',
            'installation_ids.*' => 'exists:installations,id',
            'status' => 'required|string',
            'verified_by_health_officer' => 'required|boolean'
        ]);

        $user = User::find($request->user_id);

        // Check if user is a technician
        if (!$user->isTechnicianTeam()) {
            return response()->json([
                'error' => 'User must be a Technician Lead or Technician Assistant'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $successfulAssignments = [];
            $failedAssignments = [];

            foreach ($request->installation_ids as $installationId) {
                // Check if installation is already assigned to any user
                $existingAssignment = InstallationAssignment::where('installation_id', $installationId)->first();

                if ($existingAssignment) {
                    $failedAssignments[] = [
                        'installation_id' => $installationId,
                        'error' => 'Installation already assigned to user ID: ' . $existingAssignment->user_id
                    ];
                    continue;
                }

                // Create the assignment
                $assignment = InstallationAssignment::create([
                    'user_id' => $request->user_id,
                    'installation_id' => $installationId,
                    'status' => $request->status,
                    'verified_by_health_officer' => $request->verified_by_health_officer
                ]);

                $successfulAssignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'message' => 'Bulk assignment completed',
                'successful_assignments' => count($successfulAssignments),
                'failed_assignments' => count($failedAssignments),
                'successful' => $successfulAssignments,
                'failed' => $failedAssignments,
                'success' => true,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Bulk assignment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternative bulk assignment - all or nothing
     */
    public function bulkAssignAtomic(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'installation_ids' => 'required|array',
            'installation_ids.*' => 'exists:installation_sites,id',
            'status' => 'required|string',
            'verified_by_health_officer' => 'required|boolean'
        ]);

        $user = User::find($request->user_id);

        if (!$user->isTechnicianTeam()) {
            return response()->json([
                'error' => 'User must be a Technician Lead or Technician Assistant'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if any installation is already assigned
            $existingAssignments = InstallationAssignment::whereIn('installation_site_id', $request->installation_ids)->get();

            if ($existingAssignments->count() > 0) {
                $alreadyAssigned = $existingAssignments->pluck('installation_site_id')->toArray();

                return response()->json([
                    'error' => 'Some installations are already assigned',
                    'already_assigned' => $alreadyAssigned
                ], 422);
            }

            $assignments = [];
            foreach ($request->installation_ids as $installationId) {
                $assignment = InstallationAssignment::create([
                    'user_id' => $request->user_id,
                    'installation_site_id' => $installationId,
                    'status' => $request->status,
                    'verified_by_health_officer' => $request->verified_by_health_officer
                ]);

                $assignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'message' => 'All installations assigned successfully',
                'data' => $assignments
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Bulk assignment failed: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getTechnicianAssignmentStatus($technicianId)
{
    $assignments = InstallationAssignment::where('user_id', $technicianId)
        ->pluck('installation_id');

    return response()->json([
        'success' => true,
        'data' => $assignments
    ]);
}
}
