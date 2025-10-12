<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Facility;
use App\Models\Installation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Services\InstallationImportService;

class InstallationController extends Controller
{
    public function __construct(
        private InstallationImportService $importService
    ) {}

    public function importInstallations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ], [
            'excel_file.required' => 'Please select an Excel file to upload',
            'excel_file.file' => 'The uploaded file is not valid',
            'excel_file.mimes' => 'The file must be an Excel file (xlsx, xls, csv)',
            'excel_file.max' => 'The file size must not exceed 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('excel_file');
            $data = Excel::toCollection(null, $file)->first();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Excel file is empty'
                ], 422);
            }

            $data = $data->slice(1);

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found in the Excel file'
                ], 422);
            }

            $this->importService->importInstallations($data);

            $stats = $this->importService->getImportStats();

            return response()->json([
                'success' => true,
                'message' => 'Installations imported successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            \Log::error('Installation import failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }




     /**
     * Display a listing of installations with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Installation::with([
                'facility.state',
                'facility.lga',
                'healthOfficer',
                'technician',
                'shipment',
                'delivery'
            ]);

            // Apply filters
            if ($request->has('state_id') && $request->state_id) {
                $query->whereHas('facility.state', function($q) use ($request) {
                    $q->where('id', $request->state_id);
                });
            }

            if ($request->has('lga_id') && $request->lga_id) {
                $query->whereHas('facility.lga', function($q) use ($request) {
                    $q->where('id', $request->lga_id);
                });
            }

            if ($request->has('facility_id') && $request->facility_id) {
                $query->where('facility_id', $request->facility_id);
            }

            if ($request->has('verified') && $request->verified !== '') {
                $query->where('verified_by_health_officer', $request->verified);
            }

            if ($request->has('supplier') && $request->supplier) {
                $query->where('supplier', 'like', '%' . $request->supplier . '%');
            }

            if ($request->has('product_model') && $request->product_model) {
                $query->where('product_model', 'like', '%' . $request->product_model . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $installations = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $installations,
                'message' => 'Installations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified installation
     */
    public function show($id): JsonResponse
    {
        try {
            $installation = Installation::with([
                'facility.state',
                'facility.lga',
                'healthOfficer',
                'technician',
                'shipment',
                'delivery'
            ])->find($id);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $installation,
                'message' => 'Installation retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get installation statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_installations' => Installation::count(),
                'verified_installations' => Installation::where('installation_status', 'installed')->count(),
                'pending_verification' => Installation::where('delivery_status', 'delivered')->count(),
                'installations_by_state' => State::withCount(['installations'])->get(),
                'installations_by_supplier' => Installation::select('supplier')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('supplier')
                    ->get(),
                'installations_by_model' => Installation::select('product_model')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('product_model')
                    ->get(),
                'recent_installations' => Installation::with('facility')
                    ->latest()
                    ->limit(10)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get installations by state
     */
    public function byState($stateId): JsonResponse
    {
        try {
            $installations = Installation::with([
                'facility',
                'facility.lga',
                'healthOfficer'
            ])->whereHas('facility.state', function($query) use ($stateId) {
                $query->where('id', $stateId);
            })->paginate(15);

            $state = State::find($stateId);

            return response()->json([
                'success' => true,
                'data' => [
                    'state' => $state,
                    'installations' => $installations
                ],
                'message' => 'Installations by state retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installations by state: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get installations by facility
     */
    public function byFacility($facilityId): JsonResponse
    {
        try {
            $installations = Installation::with([
                'healthOfficer',
                'technician',
                'shipment'
            ])->where('facility_id', $facilityId)->get();

            $facility = Facility::with(['state', 'lga'])->find($facilityId);

            return response()->json([
                'success' => true,
                'data' => [
                    'facility' => $facility,
                    'installations' => $installations
                ],
                'message' => 'Installations by facility retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installations by facility: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update installation verification status
     */
    public function updateVerification(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'verified' => 'required|boolean'
            ]);

            $installation = Installation::find($id);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            $installation->update([
                'verified_by_health_officer' => $request->verified
            ]);

            return response()->json([
                'success' => true,
                'data' => $installation,
                'message' => 'Verification status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update verification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search installations
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = Installation::with([
                'facility.state',
                'facility.lga',
                'healthOfficer'
            ]);

            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('supplier', 'like', "%{$searchTerm}%")
                      ->orWhere('product_model', 'like', "%{$searchTerm}%")
                      ->orWhere('po_number', 'like', "%{$searchTerm}%")
                      ->orWhereHas('facility', function($q) use ($searchTerm) {
                          $q->where('name', 'like', "%{$searchTerm}%");
                      })
                      ->orWhereHas('healthOfficer', function($q) use ($searchTerm) {
                          $q->where('name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 15);
            $installations = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $installations,
                'message' => 'Search results retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }


    // Add to InstallationController
public function updateDeliveryStatus(Request $request, $id): JsonResponse
{
    try {
        $request->validate([
            'status' => 'required|string|in:not delivered,in transit,delivered,partially delivered'
        ]);

        $installation = Installation::find($id);
        if (!$installation) {
            return response()->json([
                'success' => false,
                'message' => 'Installation not found'
            ], 404);
        }

        $installation->update(['delivery_status' => $request->status]);

        return response()->json([
            'success' => true,
            'data' => $installation,
            'message' => 'Delivery status updated successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update delivery status: ' . $e->getMessage()
        ], 500);
    }
}

public function updateInstallationStatus(Request $request, $id): JsonResponse
{
    try {
        $request->validate([
            'status' => 'required|string|in:not installed,in progress,installed,partially installed'
        ]);

        $installation = Installation::find($id);
        if (!$installation) {
            return response()->json([
                'success' => false,
                'message' => 'Installation not found'
            ], 404);
        }

        $installation->update(['installation_status' => $request->status]);

        return response()->json([
            'success' => true,
            'data' => $installation,
            'message' => 'Installation status updated successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update installation status: ' . $e->getMessage()
        ], 500);
    }
}
}
