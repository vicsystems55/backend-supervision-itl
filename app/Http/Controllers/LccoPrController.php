<?php

namespace App\Http\Controllers;

use App\Models\LccoPr;
use App\Models\Installation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LccoPrController extends Controller
{
    /**
     * List LccoPr records (with optional filters)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LccoPr::with('installation');

            if ($request->has('installation_id') && $request->installation_id) {
                $query->where('installation_id', $request->installation_id);
            }

            if ($request->has('lcco_name') && $request->lcco_name) {
                $query->where('lcco_name', 'like', '%' . $request->lcco_name . '%');
            }

            $perPage = $request->get('per_page', 15);
            $items = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $items,
                'message' => 'Lcco records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Lcco records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new LccoPr
     */
    public function store(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'installation_id' => 'required|exists:installations,id',
            'lcco_name' => 'required|string|max:255',
            'lcco_phone' => 'nullable|string|max:50',
            'device_tag_code' => 'nullable|string|max:255',
            'device_serial_number' => 'nullable|string|max:255',
            'installation_status' => 'nullable|string|max:255',
            'lcco_account_number' => 'nullable|string|max:255',
            'lcco_bank_name' => 'nullable|string|max:255',
            'lcco_account_name' => 'nullable|string|max:255',
            'payment_status' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lcco = LccoPr::updateOrCreate([
                'installation_id' => $request->installation_id
            ],$request->only([
                'installation_id',
                'lcco_name',
                'lcco_phone',
                'device_tag_code',
                'device_serial_number',
                'installation_status',
                'lcco_account_number',
                'lcco_bank_name',
                'lcco_account_name',
                'payment_status'
            ]));

            return response()->json([
                'success' => true,
                'data' => $lcco,
                'message' => 'Lcco record created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Lcco record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific LccoPr
     */
    public function show($id): JsonResponse
    {
        try {
            $lcco = LccoPr::with('installation')->find($id);
            if (!$lcco) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lcco record not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lcco,
                'message' => 'Lcco record retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Lcco record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing LccoPr
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lcco_name' => 'nullable|string|max:255',
            'lcco_phone' => 'nullable|string|max:50',
            'device_tag_code' => 'nullable|string|max:255',
            'device_serial_number' => 'nullable|string|max:255',
            'installation_status' => 'nullable|string|max:255',
            'lcco_account_number' => 'nullable|string|max:255',
            'lcco_bank_name' => 'nullable|string|max:255',
            'lcco_account_name' => 'nullable|string|max:255',
            'payment_status' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lcco = LccoPr::find($id);
            if (!$lcco) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lcco record not found'
                ], 404);
            }

            $lcco->update($request->only([
                'lcco_name',
                'lcco_phone',
                'device_tag_code',
                'device_serial_number',
                'installation_status',
                'lcco_account_number',
                'lcco_bank_name',
                'lcco_account_name',
                'payment_status'
            ]));

            return response()->json([
                'success' => true,
                'data' => $lcco,
                'message' => 'Lcco record updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Lcco record: ' . $e->getMessage()
            ], 500);
        }
    }
}
