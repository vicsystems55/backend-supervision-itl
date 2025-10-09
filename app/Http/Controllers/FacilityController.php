<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Services\StateFacilityImportService;

class FacilityController extends Controller
{
    public function __construct(
        private StateFacilityImportService $importService
    ) {}

    public function importStatesAndFacilities(Request $request): JsonResponse
    {
        // Manual validation
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max
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
            // Get the uploaded file
            $file = $request->file('excel_file');

            // Read the Excel file
            $data = Excel::toCollection(null, $file)->first();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The Excel file is empty'
                ], 422);
            }

            // Remove header row
            $data = $data->slice(1);

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found in the Excel file (after removing headers)'
                ], 422);
            }

            // Import the data
            $this->importService->importStatesAndFacilities($data);

            // Get statistics
            $stats = $this->importService->getImportStats();

            return response()->json([
                'success' => true,
                'message' => 'Data imported successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Facility import failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed. Please check the file format and try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getImportStats(): JsonResponse
    {
        $stats = $this->importService->getImportStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
