<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Technician;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FacilityTechnicianAssignmentController extends Controller
{
    /**
     * Get all available facilities for assignment
     */
    public function availableFacilities(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $type = $request->get('type', '');

            $facilities = Facility::where('active', true)
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('location', 'like', "%{$search}%")
                          ->orWhere('contact_person', 'like', "%{$search}%");
                })
                ->when($type, function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'location', 'type', 'contact_person', 'phone', 'email']);

            return response()->json([
                'success' => true,
                'facilities' => $facilities,
                'total' => $facilities->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available facilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available technicians for assignment
     */
    public function availableTechnicians(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $designation = $request->get('designation', '');
            $specialization = $request->get('specialization', '');

            $technicians = Technician::where('active', true)
                ->whereDoesntHave('installations', function ($query) {
                    $query->whereNull('actual_installation_end_date'); // Not currently assigned to active installations
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('specialization', 'like', "%{$search}%");
                })
                ->when($designation, function ($query) use ($designation) {
                    $query->where('designation', $designation);
                })
                ->when($specialization, function ($query) use ($specialization) {
                    $query->where('specialization', 'like', "%{$specialization}%");
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'designation', 'specialization']);

            return response()->json([
                'success' => true,
                'technicians' => $technicians,
                'total' => $technicians->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available technicians',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign a technician to a facility (create installation)
     */
    public function assignTechnician(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'technician_id' => 'required|exists:technicians,id',
                'facility_id' => 'required|exists:facilities,id',
                'product_model' => 'required|string|max:255',
                'po_number' => 'nullable|string|max:100',
                'po_item_number' => 'nullable|string|max:50',
                'service_contract_number' => 'nullable|string|max:100',
                'total_quantity_received' => 'nullable|integer|min:0',
                'total_quantity_delivered' => 'nullable|integer|min:0',
                'total_quantity_installed' => 'nullable|integer|min:0',
                'date_received_in_country' => 'nullable|date',
                'planned_installation_end_date' => 'nullable|date|after_or_equal:today',
                'province' => 'nullable|string|max:100',
                'supplier' => 'nullable|string|max:255',
                'remarks' => 'nullable|string',
                'supplier_comments' => 'nullable|string',
                'shipment_id' => 'nullable|exists:shipments,id',
                'delivery_id' => 'nullable|exists:deliveries,id',
            ]);

            DB::beginTransaction();

            // Check if technician is available
            $technician = Technician::findOrFail($validated['technician_id']);
            if (!$technician->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign inactive technician',
                ], 422);
            }

            // Check if facility is active
            $facility = Facility::findOrFail($validated['facility_id']);
            if (!$facility->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign to inactive facility',
                ], 422);
            }

            // Check if technician already has too many active assignments (max 3)
            $activeAssignments = Installation::where('technician_id', $technician->id)
                ->whereNull('actual_installation_end_date')
                ->count();

            if ($activeAssignments >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician already has maximum active assignments (3)',
                ], 422);
            }

            // Create the installation
            $installation = Installation::create([
                'technician_id' => $validated['technician_id'],
                'facility_id' => $validated['facility_id'],
                'country' => 'Nigeria',
                'number_of_deviations' => 0,
                'verified_by_health_officer' => false,
                ...$validated
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician assigned to facility successfully',
                'installation' => $installation->load(['facility', 'technician', 'shipment', 'delivery']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign technician to facility',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign multiple technicians to multiple facilities (batch assignment)
     */
    public function batchAssign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'assignments' => 'required|array|min:1',
                'assignments.*.technician_id' => 'required|exists:technicians,id',
                'assignments.*.facility_id' => 'required|exists:facilities,id',
                'assignments.*.product_model' => 'required|string|max:255',
                'assignments.*.po_number' => 'nullable|string|max:100',
                'assignments.*.po_item_number' => 'nullable|string|max:50',
                'assignments.*.service_contract_number' => 'nullable|string|max:100',
                'assignments.*.total_quantity_received' => 'nullable|integer|min:0',
                'assignments.*.total_quantity_delivered' => 'nullable|integer|min:0',
                'assignments.*.total_quantity_installed' => 'nullable|integer|min:0',
                'assignments.*.date_received_in_country' => 'nullable|date',
                'assignments.*.planned_installation_end_date' => 'nullable|date|after_or_equal:today',
                'assignments.*.province' => 'nullable|string|max:100',
                'assignments.*.supplier' => 'nullable|string|max:255',
                'assignments.*.remarks' => 'nullable|string',
                'assignments.*.supplier_comments' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $createdInstallations = [];
            $errors = [];

            foreach ($validated['assignments'] as $index => $assignment) {
                try {
                    // Check technician availability
                    $technician = Technician::find($assignment['technician_id']);
                    if (!$technician || !$technician->active) {
                        $errors[] = "Assignment {$index}: Technician is not available or inactive";
                        continue;
                    }

                    // Check facility availability
                    $facility = Facility::find($assignment['facility_id']);
                    if (!$facility || !$facility->active) {
                        $errors[] = "Assignment {$index}: Facility is not available or inactive";
                        continue;
                    }

                    // Check active assignments limit
                    $activeAssignments = Installation::where('technician_id', $technician->id)
                        ->whereNull('actual_installation_end_date')
                        ->count();

                    if ($activeAssignments >= 3) {
                        $errors[] = "Assignment {$index}: Technician {$technician->name} has maximum active assignments (3)";
                        continue;
                    }

                    // Create installation
                    $installation = Installation::create([
                        'technician_id' => $assignment['technician_id'],
                        'facility_id' => $assignment['facility_id'],
                        'country' => 'Nigeria',
                        'number_of_deviations' => 0,
                        'verified_by_health_officer' => false,
                        ...$assignment
                    ]);

                    $createdInstallations[] = $installation->load(['facility', 'technician']);

                } catch (\Exception $e) {
                    $errors[] = "Assignment {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            $response = [
                'success' => true,
                'message' => count($createdInstallations) . ' assignment(s) created successfully',
                'installations' => $createdInstallations,
                'total_created' => count($createdInstallations),
                'total_failed' => count($errors),
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                $response['message'] .= ', ' . count($errors) . ' assignment(s) failed';
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign a technician to a different installation
     */
    public function reassignTechnician(Request $request, $installationId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'new_technician_id' => 'required|exists:technicians,id',
                'reason' => 'required|string|max:500',
            ]);

            DB::beginTransaction();

            $installation = Installation::findOrFail($installationId);
            $newTechnician = Technician::findOrFail($validated['new_technician_id']);

            // Check if new technician is available
            if (!$newTechnician->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign to inactive technician',
                ], 422);
            }

            // Check active assignments limit for new technician
            $activeAssignments = Installation::where('technician_id', $newTechnician->id)
                ->whereNull('actual_installation_end_date')
                ->count();

            if ($activeAssignments >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'New technician already has maximum active assignments (3)',
                ], 422);
            }

            $oldTechnicianId = $installation->technician_id;

            // Update the installation
            $installation->update([
                'technician_id' => $validated['new_technician_id'],
                'remarks' => ($installation->remarks ? $installation->remarks . "\n\n" : '') .
                           "Reassigned from technician ID {$oldTechnicianId} to {$validated['new_technician_id']}. Reason: {$validated['reason']}",
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician reassigned successfully',
                'installation' => $installation->load(['facility', 'technician']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign technician',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove technician assignment from installation
     */
    public function removeAssignment($installationId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $installation = Installation::findOrFail($installationId);

            // Check if installation has already started
            if ($installation->total_quantity_installed > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove assignment from installation that has already started',
                ], 422);
            }

            $installation->update(['technician_id' => null]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully',
                'installation' => $installation->load('facility'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assignment statistics
     */
    public function assignmentStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_technicians' => Technician::where('active', true)->count(),
                'total_facilities' => Facility::where('active', true)->count(),
                'active_assignments' => Installation::whereNull('actual_installation_end_date')->count(),
                'completed_assignments' => Installation::whereNotNull('actual_installation_end_date')->count(),
                'available_technicians' => Technician::where('active', true)
                    ->whereDoesntHave('installations', function ($query) {
                        $query->whereNull('actual_installation_end_date');
                    })
                    ->count(),
                'technicians_with_max_assignments' => Technician::where('active', true)
                    ->withCount(['installations' => function ($query) {
                        $query->whereNull('actual_installation_end_date');
                    }])
                    ->having('installations_count', '>=', 3)
                    ->count(),
            ];

            // Recent assignments (last 7 days)
            $recentAssignments = Installation::with(['technician', 'facility'])
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'statistics' => $stats,
                'recent_assignments' => $recentAssignments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignment statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technician workload report
     */
    public function technicianWorkload(): JsonResponse
    {
        try {
            $workload = Technician::where('active', true)
                ->withCount(['installations as active_assignments_count' => function ($query) {
                    $query->whereNull('actual_installation_end_date');
                }])
                ->withCount(['installations as total_assignments_count'])
                ->with(['installations' => function ($query) {
                    $query->whereNull('actual_installation_end_date')
                          ->with('facility');
                }])
                ->orderBy('active_assignments_count', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'workload' => $workload,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch technician workload',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
