<?php

namespace App\Services;

use App\Models\State;
use App\Models\Lga;
use App\Models\Facility; // Changed from HealthFacility to Facility
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StateFacilityImportService
{
    private $importStats = [
        'states_created' => 0,
        'lgas_created' => 0,
        'facilities_created' => 0,
        'states_updated' => 0,
        'lgas_updated' => 0,
        'facilities_updated' => 0,
        'rows_processed' => 0,
        'rows_failed' => 0,
    ];

    public function importStatesAndFacilities(Collection $excelData): void
    {
        DB::beginTransaction();

        try {
            // Remove header row and get actual data
            $dataRows = $excelData->slice(1);

            if ($dataRows->isEmpty()) {
                throw new \Exception('No data rows found after removing headers');
            }

            Log::info('Starting import with ' . $dataRows->count() . ' data rows');

            // Get unique states from Excel
            $uniqueStates = $dataRows->pluck(2)->unique()->filter();

            Log::info('Unique states found:', $uniqueStates->toArray());

            $this->importStats['states_processed'] = $uniqueStates->count();

            foreach ($uniqueStates as $stateName) {
                if (empty(trim($stateName))) {
                    continue;
                }

                // Create or update state
                $state = State::firstOrCreate(
                    ['name' => trim($stateName)],
                    ['active' => true]
                );

                if ($state->wasRecentlyCreated) {
                    $this->importStats['states_created']++;
                } else {
                    $this->importStats['states_updated']++;
                }

                // Get LGAs for this state
                $stateLgas = $dataRows
                    ->where(2, $stateName)
                    ->pluck(3)
                    ->unique()
                    ->filter();

                foreach ($stateLgas as $lgaName) {
                    if (empty(trim($lgaName))) {
                        continue;
                    }

                    // Create or update LGA
                    $lga = Lga::firstOrCreate(
                        [
                            'state_id' => $state->id,
                            'name' => trim($lgaName)
                        ],
                        ['active' => true]
                    );

                    if ($lga->wasRecentlyCreated) {
                        $this->importStats['lgas_created']++;
                    } else {
                        $this->importStats['lgas_updated']++;
                    }

                    // Get facilities for this LGA
                    $facilities = $dataRows
                        ->where(2, $stateName)
                        ->where(3, $lgaName);

                    foreach ($facilities as $row) {
                        $this->importStats['rows_processed']++;

                        try {
                            $facilityName = $row[5] ?? '';

                            if (empty(trim($facilityName))) {
                                $this->importStats['rows_failed']++;
                                continue;
                            }

                            // Use Facility model instead of HealthFacility
                            $facility = Facility::updateOrCreate(
                                [
                                    'state_id' => $state->id,
                                    'lga_id' => $lga->id,
                                    'name' => trim($facilityName)
                                ],
                                [
                                    'address' => trim($row[4] ?? $row[3] ?? ''),
                                    'supply_chain_level' => $row[1] ?? 'Lowest delivery point',
                                    'road_accessible' => isset($row[16]) && strtolower($row[16]) === 'yes',
                                    'distance_from_hub_km' => intval($row[17] ?? 0),
                                    'road_quality' => $row[18] ?? null,
                                    'facility_type' => 'Cold Store'
                                ]
                            );

                            if ($facility->wasRecentlyCreated) {
                                $this->importStats['facilities_created']++;
                                Log::info("Created facility: {$facilityName}");
                            } else {
                                $this->importStats['facilities_updated']++;
                                Log::info("Updated facility: {$facilityName}");
                            }
                        } catch (\Exception $e) {
                            $this->importStats['rows_failed']++;
                            Log::error('Failed to import facility: ' . $e->getMessage(), [
                                'state' => $stateName,
                                'lga' => $lgaName,
                                'facility_name' => $facilityName ?? 'Unknown'
                            ]);
                            continue;
                        }
                    }
                }
            }

            DB::commit();
            Log::info('Import completed successfully', $this->importStats);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Facility import transaction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getImportStats(): array
    {
        return array_merge([
            'total_states' => State::count(),
            'total_lgas' => Lga::count(),
            'total_facilities' => Facility::count(), // Changed to Facility
        ], $this->importStats);
    }
}
