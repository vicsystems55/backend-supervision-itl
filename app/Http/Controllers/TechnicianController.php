<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\Installation;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    /**
     * Display a listing of technicians with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            $designation = $request->get('designation', '');
            $status = $request->get('status', '');

            $technicians = Technician::with(['user.roles', 'installations.facility'])
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('id_card_number', 'like', "%{$search}%");
                })
                ->when($designation, function ($query) use ($designation) {
                    $query->where('designation', $designation);
                })
                ->when($status === 'active', function ($query) {
                    $query->where('active', true);
                })
                ->when($status === 'inactive', function ($query) {
                    $query->where('active', false);
                })
                ->orderBy('name')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'technicians' => $technicians,
                'total' => $technicians->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch technicians',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function userData($userId) {
    try {
        $user = User::with('technician')->findOrFail($userId);

        if (!$user->technician) {
            return response()->json([
                'success' => false,
                'message' => 'No associated technician profile found for this user',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'technician' => $user->technician
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch user data',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Store a newly created technician.
     */
public function store(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'account_name' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'id_card_number' => 'nullable|string|max:50|unique:technicians,id_card_number',
            'active' => 'boolean',
        ]);

        DB::beginTransaction();

        // Fetch the user and their details
        $user = User::findOrFail($validated['user_id']);

        // Check if technician already exists for this user
        // if (Technician::where('user_id', $validated['user_id'])->exists()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Technician profile already exists for this user',
        //     ], 422);
        // }

        // Create technician with user's name and email
        $technician = Technician::updateOrCreate([
            'user_id' => $validated['user_id'],

        ],[
            'user_id' => $validated['user_id'],
            'name' => $user->name, // Get name from user
            'email' => $user->email, // Get email from user
            'phone' => $validated['phone'] ?? null,
            'designation' => 'technician',
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'id_card_number' => $validated['id_card_number'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Technician created successfully',
            'technician' => $technician->load(['user.roles', 'installations.facility']),
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create technician',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified technician with detailed information.
     */
    public function show($id): JsonResponse
    {
        try {
            $technician = Technician::with([
                'user.roles',
                'installations.facility',
                'installations.shipment',
                'installations.delivery'
            ])->findOrFail($id);

            // Get statistics
            $stats = [
                'total_installations' => $technician->installations->count(),
                'completed_installations' => $technician->installations->where('actual_installation_end_date', '!=', null)->count(),
                'ongoing_installations' => $technician->installations->where('actual_installation_end_date', null)->count(),
                'verified_installations' => $technician->installations->where('verified_by_health_officer', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'technician' => $technician,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Technician not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified technician.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => [
                    'sometimes',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('technicians')->ignore($technician->id),
                ],
                'phone' => 'nullable|string|max:20',
                'designation' => 'sometimes|in:team_lead,technician,helper,adhoc',
                'bank_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:20',
                'account_name' => 'nullable|string|max:255',
                'specialization' => 'nullable|string|max:255',
                'id_card_number' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('technicians')->ignore($technician->id),
                ],
                'active' => 'sometimes|boolean',
            ]);

            DB::beginTransaction();

            $technician->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician updated successfully',
                'technician' => $technician->load(['user.roles', 'installations.facility']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update technician',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified technician.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            DB::beginTransaction();

            // Check if technician has active installations
            $activeInstallations = $technician->installations()
                ->whereNull('actual_installation_end_date')
                ->exists();

            if ($activeInstallations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete technician with active installations',
                ], 422);
            }

            $technician->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete technician',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get installations assigned to a specific technician.
     */
    public function technicianInstallations($id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            $installations = Installation::with([
                'facility',
                'shipment',
                'delivery',
                'healthOfficer'
            ])
            ->where('technician_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

            return response()->json([
                'success' => true,
                'installations' => $installations,
                'technician' => $technician->only(['id', 'name', 'designation']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch technician installations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign installations to a technician.
     */
    public function assignInstallations(Request $request, $id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            $validated = $request->validate([
                'installations' => 'required|array',
                'installations.*.facility_id' => 'required|exists:facilities,id',
                'installations.*.product_model' => 'required|string|max:255',
                'installations.*.po_number' => 'nullable|string|max:100',
                'installations.*.po_item_number' => 'nullable|string|max:50',
                'installations.*.service_contract_number' => 'nullable|string|max:100',
                'installations.*.total_quantity_received' => 'nullable|integer|min:0',
                'installations.*.total_quantity_delivered' => 'nullable|integer|min:0',
                'installations.*.total_quantity_installed' => 'nullable|integer|min:0',
                'installations.*.date_received_in_country' => 'nullable|date',
                'installations.*.planned_installation_end_date' => 'nullable|date',
                'installations.*.province' => 'nullable|string|max:100',
                'installations.*.supplier' => 'nullable|string|max:255',
                'installations.*.remarks' => 'nullable|string',
                'installations.*.supplier_comments' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $createdInstallations = [];

            foreach ($validated['installations'] as $installationData) {
                $installation = Installation::create([
                    'technician_id' => $technician->id,
                    'country' => 'Nigeria',
                    'number_of_deviations' => 0,
                    'verified_by_health_officer' => false,
                    ...$installationData
                ]);

                $createdInstallations[] = $installation->load('facility');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdInstallations) . ' installation(s) assigned successfully',
                'installations' => $createdInstallations,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign installations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove installation assignment from technician.
     */
    public function removeInstallation($technicianId, $installationId): JsonResponse
    {
        try {
            $installation = Installation::where('technician_id', $technicianId)
                ->where('id', $installationId)
                ->firstOrFail();

            DB::beginTransaction();

            // Only allow removal if installation hasn't started
            if ($installation->total_quantity_installed > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove installation that has already started',
                ], 422);
            }

            $installation->update(['technician_id' => null]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Installation assignment removed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove installation assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technician statistics and dashboard data.
     */
    public function statistics($id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            $stats = [
                'total_assignments' => $technician->installations->count(),
                'completed_assignments' => $technician->installations->whereNotNull('actual_installation_end_date')->count(),
                'pending_assignments' => $technician->installations->whereNull('actual_installation_end_date')->count(),
                'verified_assignments' => $technician->installations->where('verified_by_health_officer', true)->count(),
                'completion_rate' => $technician->installations->count() > 0
                    ? round(($technician->installations->whereNotNull('actual_installation_end_date')->count() / $technician->installations->count()) * 100, 2)
                    : 0,
            ];

            // Recent activity (last 5 installations)
            $recentActivity = $technician->installations()
                ->with('facility')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($installation) {
                    return [
                        'id' => $installation->id,
                        'facility_name' => $installation->facility->name,
                        'product_model' => $installation->product_model,
                        'status' => $installation->actual_installation_end_date ? 'completed' : 'in_progress',
                        'updated_at' => $installation->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'technician' => [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'designation' => $technician->designation,
                    'active' => $technician->active,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch technician statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle technician active status.
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $technician = Technician::findOrFail($id);

            DB::beginTransaction();

            $technician->update([
                'active' => !$technician->active
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Technician status updated successfully',
                'technician' => $technician->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update technician status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available facilities for assignment.
     */
    public function availableFacilities(): JsonResponse
    {
        try {
            $facilities = Facility::where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location', 'type', 'contact_person']);

            return response()->json([
                'success' => true,
                'facilities' => $facilities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available facilities',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
