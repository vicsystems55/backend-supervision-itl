<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Checklist;
use App\Models\ChecklistItem;

class InstallationChecklistSeeder extends Seeder
{
    public function run(): void
    {
        // Create the checklist template
        $checklist = Checklist::create([
            'title' => 'Haier Biomedical Solar Direct Drive Refrigerator Installation Checklist',
            'reference_code' => 'Annex E',
            'description' => 'Standard installation verification checklist for solar-powered cold chain equipment.',
        ]);

        $items = [
            // 🔹 Check 1 – System Description
            ['section' => 'Check 1 – System Description', 'question' => 'Is the system type and model correct (e.g., HTD-40 SDD)?'],
            ['section' => 'Check 1 – System Description', 'question' => 'Is the supplier identified and verified?'],
            ['section' => 'Check 1 – System Description', 'question' => 'Does the system include all required components (refrigerator, control unit, solar panels, accessories)?'],

            // 🔹 Check 2 – Shipment Details
            ['section' => 'Check 2 – Shipment Details', 'question' => 'Was the shipment received in good condition?'],
            ['section' => 'Check 2 – Shipment Details', 'question' => 'Were all items checked against the packing list?'],
            ['section' => 'Check 2 – Shipment Details', 'question' => 'Were any items missing or damaged?'],

            // 🔹 Check 3 – Solar Panel Installation
            ['section' => 'Check 3 – Solar Panel Installation', 'question' => 'Are solar panels installed on a secure structure with correct orientation and tilt?'],
            ['section' => 'Check 3 – Solar Panel Installation', 'question' => 'Are panels free of shading or obstructions?'],
            ['section' => 'Check 3 – Solar Panel Installation', 'question' => 'Is the wiring properly connected and protected?'],
            ['section' => 'Check 3 – Solar Panel Installation', 'question' => 'Is lightning protection installed and functional?'],

            // 🔹 Check 4 – Control Unit and Electrical Connections
            ['section' => 'Check 4 – Control Unit and Electrical Connections', 'question' => 'Is the control unit securely mounted and easily accessible?'],
            ['section' => 'Check 4 – Control Unit and Electrical Connections', 'question' => 'Are all cables properly terminated and insulated?'],
            ['section' => 'Check 4 – Control Unit and Electrical Connections', 'question' => 'Are cable runs tidy and protected from damage?'],

            // 🔹 Check 5 – Refrigerator Installation
            ['section' => 'Check 5 – Refrigerator Installation', 'question' => 'Is the refrigerator installed on a level surface?'],
            ['section' => 'Check 5 – Refrigerator Installation', 'question' => 'Is the ventilation around the refrigerator adequate?'],
            ['section' => 'Check 5 – Refrigerator Installation', 'question' => 'Is the lid or door sealing properly?'],
            ['section' => 'Check 5 – Refrigerator Installation', 'question' => 'Are temperature monitoring devices installed and functional?'],

            // 🔹 Check 6 – System Performance
            ['section' => 'Check 6 – System Performance', 'question' => 'Has the system been powered on and tested for operation?'],
            ['section' => 'Check 6 – System Performance', 'question' => 'Does the control unit show correct voltage and performance readings?'],
            ['section' => 'Check 6 – System Performance', 'question' => 'Is the refrigerator maintaining the required temperature range?'],

            // 🔹 Check 7 – Documentation and Training
            ['section' => 'Check 7 – Documentation and Training', 'question' => 'Has the installation report and checklist been completed?'],
            ['section' => 'Check 7 – Documentation and Training', 'question' => 'Has the facility staff been trained on equipment operation and maintenance?'],
            ['section' => 'Check 7 – Documentation and Training', 'question' => 'Have warranty and service documents been handed over to the facility?'],

            // 🔹 Check 8 – Final Sign-Off
            ['section' => 'Check 8 – Final Sign-Off', 'question' => 'Has the installation been verified by the health officer?'],
            ['section' => 'Check 8 – Final Sign-Off', 'question' => 'Have all deviations been documented and resolved?'],
            ['section' => 'Check 8 – Final Sign-Off', 'question' => 'Are photographs of the installation site attached?'],
        ];

        foreach ($items as $item) {
            ChecklistItem::create([
                'checklist_id' => $checklist->id,
                'section' => $item['section'],
                'question' => $item['question'],
                'type' => 'yes_no',
                'required' => true,
            ]);
        }

        echo "✅ Installation Checklist seeded successfully.\n";
    }
}
