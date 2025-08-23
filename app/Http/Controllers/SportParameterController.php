<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use App\Models\SportParameter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SportParameterController extends Controller
{
    /**
     * Display a listing of the sport parameters.
     */
    public function index(Sport $sport): JsonResponse
    {
        $parameters = $sport->parameters()
                           ->orderBy('param_key')
                           ->get();

        return response()->json([
            'data' => $parameters,
            'message' => 'Sport parameters retrieved successfully',
        ]);
    }

    /**
     * Store a newly created sport parameter.
     */
    public function store(Request $request, Sport $sport): JsonResponse
    {
        $request->validate([
            'param_key' => 'required|string|max:255',
            'param_type' => 'required|in:number,string,boolean',
            'param_value' => 'required',
        ]);

        // Additional validation based on param_type
        $this->validateParameterValue($request);

        // Check if parameter key already exists for this sport
        $existingParameter = $sport->parameters()
                                  ->where('param_key', $request->param_key)
                                  ->first();

        if ($existingParameter) {
            return response()->json([
                'message' => 'Parameter key already exists for this sport',
                'errors' => ['param_key' => ['This parameter key already exists']],
            ], 422);
        }

        $parameter = $sport->parameters()->create([
            'param_key' => $request->param_key,
            'param_type' => $request->param_type,
            'param_value' => $this->formatParameterValue($request->param_value, $request->param_type),
        ]);

        return response()->json([
            'data' => $parameter,
            'message' => 'Sport parameter created successfully',
        ], 201);
    }

    /**
     * Update the specified sport parameter.
     */
    public function update(Request $request, Sport $sport, SportParameter $parameter): JsonResponse
    {
        // Ensure parameter belongs to the sport
        if ($parameter->sport_id !== $sport->id) {
            return response()->json([
                'message' => 'Parameter does not belong to this sport',
            ], 404);
        }

        $request->validate([
            'param_key' => 'required|string|max:255',
            'param_type' => 'required|in:number,string,boolean',
            'param_value' => 'required',
        ]);

        // Additional validation based on param_type
        $this->validateParameterValue($request);

        // Check if parameter key already exists for this sport (excluding current parameter)
        $existingParameter = $sport->parameters()
                                  ->where('param_key', $request->param_key)
                                  ->where('id', '!=', $parameter->id)
                                  ->first();

        if ($existingParameter) {
            return response()->json([
                'message' => 'Parameter key already exists for this sport',
                'errors' => ['param_key' => ['This parameter key already exists']],
            ], 422);
        }

        $parameter->update([
            'param_key' => $request->param_key,
            'param_type' => $request->param_type,
            'param_value' => $this->formatParameterValue($request->param_value, $request->param_type),
        ]);

        return response()->json([
            'data' => $parameter,
            'message' => 'Sport parameter updated successfully',
        ]);
    }

    /**
     * Remove the specified sport parameter.
     */
    public function destroy(Sport $sport, SportParameter $parameter): JsonResponse
    {
        // Ensure parameter belongs to the sport
        if ($parameter->sport_id !== $sport->id) {
            return response()->json([
                'message' => 'Parameter does not belong to this sport',
            ], 404);
        }

        $parameter->delete();

        return response()->json([
            'message' => 'Sport parameter deleted successfully',
        ]);
    }

    /**
     * Validate parameter value based on type.
     */
    private function validateParameterValue(Request $request): void
    {
        switch ($request->param_type) {
            case 'number':
                $request->validate([
                    'param_value' => 'numeric',
                ]);
                break;
            case 'boolean':
                $request->validate([
                    'param_value' => 'boolean',
                ]);
                break;
            case 'string':
                $request->validate([
                    'param_value' => 'string|max:1000',
                ]);
                break;
        }
    }

    /**
     * Format parameter value based on type.
     */
    private function formatParameterValue($value, string $type): string
    {
        return match ($type) {
            'number' => (string) (float) $value,
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}