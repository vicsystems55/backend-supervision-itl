<?php
// app/Http/Controllers/StateController.php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends Controller
{
public function index(Request $request): JsonResponse
{
    try {
        $states = State::withCount([
            'installations as total_supposed_installations',
            'installations as total_installed' => function ($query) {
                $query->where('installation_status', 'installed');
            },
            'installations as total_delivered' => function ($query) {
                $query->where('delivery_status', 'delivered');
            }
        ])->get(['id', 'name', 'code', 'latitude', 'longitude']);

        // Alternative approach if you need more detailed aggregation
        // $states = State::with(['installations'])->get(['id', 'name', 'code', 'latitude', 'longitude']);

        // Transform the data to include the counts
        $statesWithDetails = $states->map(function ($state) {
            return [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'latitude' => $state->latitude,
                'longitude' => $state->longitude,
                'installation_details' => [
                    'total_supposed_installations' => $state->total_supposed_installations,
                    'total_installed' => $state->total_installed,
                    'total_delivered' => $state->total_delivered,
                    'installation_rate' => $state->total_supposed_installations > 0
                        ? round(($state->total_installed / $state->total_supposed_installations) * 100, 2)
                        : 0,
                    'delivery_rate' => $state->total_supposed_installations > 0
                        ? round(($state->total_delivered / $state->total_supposed_installations) * 100, 2)
                        : 0,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $statesWithDetails,
            'message' => 'States with installation details retrieved successfully'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve states with installation details',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function show($id): JsonResponse
    {
        try {
            $state = State::with('lgas')->find($id);

            if (!$state) {
                return response()->json([
                    'success' => false,
                    'message' => 'State not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $state,
                'message' => 'State retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve state',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:states,name',
                'code' => 'nullable|string|max:10|unique:states,code',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'active' => 'boolean'
            ]);

            $state = State::create($validated);

            return response()->json([
                'success' => true,
                'data' => $state,
                'message' => 'State created successfully'
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
                'message' => 'Failed to create state',
                'error' => $e->getMessage()
            ], 500);
        }
    }




}
