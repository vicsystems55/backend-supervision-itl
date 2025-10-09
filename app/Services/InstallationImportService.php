<?php

namespace App\Services;

use App\Models\Installation;
use App\Models\Facility;
use App\Models\HealthOfficer;
use App\Models\Shipment;
use App\Models\Technician;
use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class InstallationImportService
{
    private $importStats = [
        'installations_created' => 0,
        'installations_updated' => 0,
        'health_officers_created' => 0,
        'rows_processed' => 0,
        'rows_failed' => 0,
    ];

    public function importInstallations(Collection $excelData): void
    {
        DB::beginTransaction();

        try {
            $dataRows = $excelData->slice(1);

            if ($dataRows->isEmpty()) {
                throw new \Exception('No data rows found after removing headers');
            }

            foreach ($dataRows as $row) {
                $this->importStats['rows_processed']++;

                try {
                    $facilityName = $row[5] ?? ''; // Facility Name from Excel
                    $officerName = $row[6] ?? ''; // Officer in Charge Name
                    $officerPhone = $row[7] ?? ''; // Contact Number

                    if (empty(trim($facilityName))) {
                        $this->importStats['rows_failed']++;
                        continue;
                    }

                    // Find the facility
                    $facility = Facility::where('name', trim($facilityName))->first();

                    if (!$facility) {
                        Log::warning("Facility not found: {$facilityName}");
                        $this->importStats['rows_failed']++;
                        continue;
                    }

                    // Create or find health officer
                    $healthOfficer = null;
                    if (!empty(trim($officerName))) {
                        $healthOfficer = HealthOfficer::firstOrCreate(
                            [
                                'phone' => trim($officerPhone)
                            ],
                            [
                                'name' => trim($officerName),
                                'facility_id' => $facility->id
                            ]
                        );

                        if ($healthOfficer->wasRecentlyCreated) {
                            $this->importStats['health_officers_created']++;
                        }
                    }

                    // Create installation record
                    $installation = Installation::updateOrCreate(
                        [
                            'facility_id' => $facility->id,
                            'product_model' => $row[10] ?? null, // CCE Model
                        ],
                        [
                            'province' => $row[2] ?? null, // Region/Province/States
                            'supplier' => $row[9] ?? null, // CCE Manufacturer
                            'product_model' => $row[10] ?? null, // CCE Model
                            'total_quantity_installed' => $row[15] ?? 1, // QTY (per / HF)
                            'health_officer_id' => $healthOfficer?->id,
                            'country' => 'Nigeria',
                            'remarks' => $row[8] ?? null, // Reason for deployment
                            'verified_by_health_officer' => !empty($healthOfficer),
                        ]
                    );

                    $installation->wasRecentlyCreated ?
                        $this->importStats['installations_created']++ :
                        $this->importStats['installations_updated']++;

                } catch (\Exception $e) {
                    $this->importStats['rows_failed']++;
                    Log::error('Failed to import installation: ' . $e->getMessage(), [
                        'facility_name' => $facilityName ?? 'Unknown',
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getImportStats(): array
    {
        return array_merge([
            'total_installations' => Installation::count(),
            'total_health_officers' => HealthOfficer::count(),
        ], $this->importStats);
    }
}
