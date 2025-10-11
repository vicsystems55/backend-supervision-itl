<?php
// app/Http/Controllers/LgaController.php

namespace App\Http\Controllers;

use App\Models\Lga;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LgaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lga::with('state')->get();

            // Filter by state_id if provided
            if ($request->has('state_id')) {
                $query->byState($request->state_id);
            }

            $lgas = $query->get(['id', 'state_id', 'name', 'latitude', 'longitude', 'active']);

            return response()->json([
                'success' => true,
                'data' => $lgas,
                'message' => 'LGAs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve LGAs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $lga = Lga::with(['state:id,name,code'])
                ->active()
                ->find($id);

            if (!$lga) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGA not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lga,
                'message' => 'LGA retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve LGA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'state_id' => 'required|exists:states,id',
                'name' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'active' => 'boolean'
            ]);

            // Check for duplicate LGA name within the same state
            $existingLga = Lga::where('state_id', $validated['state_id'])
                ->where('name', $validated['name'])
                ->first();

            if ($existingLga) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGA with this name already exists in the selected state'
                ], 422);
            }

            $lga = Lga::create($validated);

            return response()->json([
                'success' => true,
                'data' => $lga->load('state:id,name,code'),
                'message' => 'LGA created successfully'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create LGA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $lga = Lga::find($id);

            if (!$lga) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGA not found'
                ], 404);
            }

            $validated = $request->validate([
                'state_id' => 'sometimes|required|exists:states,id',
                'name' => 'sometimes|required|string|max:255',
                'latitude' => 'sometimes|nullable|numeric',
                'longitude' => 'sometimes|nullable|numeric',
                'active' => 'sometimes|boolean'
            ]);

            // Check for duplicate LGA name within the same state (excluding current LGA)
            if (isset($validated['state_id']) && isset($validated['name'])) {
                $existingLga = Lga::where('state_id', $validated['state_id'])
                    ->where('name', $validated['name'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingLga) {
                    return response()->json([
                        'success' => false,
                        'message' => 'LGA with this name already exists in the selected state'
                    ], 422);
                }
            }

            $lga->update($validated);

            return response()->json([
                'success' => true,
                'data' => $lga->load('state:id,name,code'),
                'message' => 'LGA updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update LGA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $lga = Lga::find($id);

            if (!$lga) {
                return response()->json([
                    'success' => false,
                    'message' => 'LGA not found'
                ], 404);
            }

            // Check if LGA has associated facilities or installations
            // You can add additional checks here based on your application

            $lga->delete();

            return response()->json([
                'success' => true,
                'message' => 'LGA deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete LGA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByState($stateId): JsonResponse
    {
        try {
            $state = State::active()->find($stateId);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'message' => 'State not found'
                ], 404);
            }

            $lgas = Lga::where('state_id', $stateId)
                ->active()
                ->ordered()
                ->get(['id', 'name', 'latitude', 'longitude']);

            return response()->json([
                'success' => true,
                'data' => $lgas,
                'message' => 'LGAs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve LGAs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
