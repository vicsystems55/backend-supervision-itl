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
            $states = State::all(['id', 'name', 'code', 'latitude', 'longitude']);

            return response()->json([
                'success' => true,
                'data' => $states,
                'message' => 'States retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve states',
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
