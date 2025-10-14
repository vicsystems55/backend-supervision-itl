<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\ChecklistSection;
use App\Models\ChecklistQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create the main checklist
            $checklist = Checklist::create([
                'name' => 'Solar Direct Drive Refrigerator Installation',
                'version' => '1.0',
                'is_active' => true,
            ]);

            // Section 1: System Description
            $section1 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 1 – System Description',
                'description' => null,
                'order' => 1,
            ]);

            $this->createQuestions($section1, [
                [
                    'question_code' => 'refrigerator_serial_number',
                    'question_text' => 'Refrigerator Serial Number',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Enter serial number',
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'product_number',
                    'question_text' => 'Product Number',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Enter product number',
                    'validation_rules' => 'required',
                    'order' => 2,
                ],
                [
                    'question_code' => 'solar_panel_model',
                    'question_text' => 'Solar Panel Model Reference',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Enter solar panel model',
                    'validation_rules' => 'required',
                    'order' => 3,
                ],
                [
                    'question_code' => 'solar_panel_quantity',
                    'question_text' => 'Quantity of Panels',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => 'Enter quantity',
                    'validation_rules' => 'required|integer|min:1',
                    'order' => 4,
                ],
            ]);

            // Section 2: Shipment Details
            $section2 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 2 – Shipment Details',
                'description' => null,
                'order' => 2,
            ]);

            $this->createQuestions($section2, [
                [
                    'question_code' => 'shipment_damaged',
                    'question_text' => 'Was the shipment damaged?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'components_missing',
                    'question_text' => 'Were any components missing or under-supplied?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 2,
                ],
                [
                    'question_code' => 'parts_replaced',
                    'question_text' => 'Have damaged/missing/under-supplied parts been replaced?',
                    'type' => 'select',
                    'options' => ['Yes', 'No', 'Not Applicable'],
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 3,
                ],
            ]);

            // Section 3: Solar Panel Installation
            $section3 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 3 – Solar Panel Installation',
                'description' => 'Note: The technician must get a good orientation where the panel has good sunlight and avoid shadow',
                'order' => 3,
            ]);

            $this->createQuestions($section3, [
                [
                    'question_code' => 'panel_orientation_correct',
                    'question_text' => 'Has the panel been installed at the correct angle towards the equator?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'shadows_on_panel',
                    'question_text' => 'Do shadows fall on the panel between 9:00am and 3:00pm?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 2,
                ],
                [
                    'question_code' => 'roof_fixings_adequate',
                    'question_text' => 'Are roof fixings in place and are they adequate?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 3,
                ],
                [
                    'question_code' => 'theft_deterrent_fasteners',
                    'question_text' => 'Have theft-deterrent fasteners been used?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 4,
                ],
                [
                    'question_code' => 'lightning_protection_fitted',
                    'question_text' => 'Has the lightning protection circuit been correctly fitted?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 5,
                ],
                [
                    'question_code' => 'earth_electrode_fitted',
                    'question_text' => 'Has the earth electrode been correctly fitted?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 6,
                ],
                [
                    'question_code' => 'lightning_protection_tested',
                    'question_text' => 'Has lightning protection system been tested for electrical continuity?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 7,
                ],
                [
                    'question_code' => 'lightning_protection_comments',
                    'question_text' => 'Comments',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Enter any comments about lightning protection',
                    'order' => 8,
                ],
            ]);

            // Section 4: Array Cabling and Installation
            $section4 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 4 – Array Cabling and Installation',
                'description' => null,
                'order' => 4,
            ]);

            $this->createQuestions($section4, [
                [
                    'question_code' => 'proper_cabling_used',
                    'question_text' => 'Only the solar array cable provided by supplier was used for installation?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'connections_protected',
                    'question_text' => 'Are all electrical connections and array cables concealed and properly protected?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 2,
                ],
                [
                    'question_code' => 'cabling_comments',
                    'question_text' => 'Comments',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Enter any comments about cabling',
                    'order' => 3,
                ],
            ]);

            // Section 5: Functionality Test
            $section5 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 5 – Functionality Test',
                'description' => null,
                'order' => 5,
            ]);

            $this->createQuestions($section5, [
                [
                    'question_code' => 'functionality_test_carried',
                    'question_text' => 'Functionality test has been carried out in accordance with the qualified supplier\'s instructions?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'functionality_report_completed',
                    'question_text' => 'A detailed functionality test report been completed and signed by the technician; a copy of the report is attached?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 2,
                ],
                [
                    'question_code' => 'gsm_coverage',
                    'question_text' => 'There is sufficient GSM coverage at the health facility?',
                    'type' => 'select',
                    'options' => ['Yes', 'No', 'Not Relevant'],
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 3,
                ],
            ]);

            // Section 6: Training
            $section6 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 6 – Training',
                'description' => null,
                'order' => 6,
            ]);

            $this->createQuestions($section6, [
                [
                    'question_code' => 'staff_trained_usage',
                    'question_text' => 'Number of health facility staff trained in usage of refrigerator',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => 'Enter number of staff',
                    'validation_rules' => 'required|integer|min:0',
                    'order' => 1,
                ],
                [
                    'question_code' => 'staff_trained_maintenance',
                    'question_text' => 'Number of staff trained in preventive maintenance of refrigerator',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => 'Enter number of staff',
                    'validation_rules' => 'required|integer|min:0',
                    'order' => 2,
                ],
                [
                    'question_code' => 'staff_trained_temperature',
                    'question_text' => 'Number of staff trained in usage of 30 DTR / recording of temperature',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => 'Enter number of staff',
                    'validation_rules' => 'required|integer|min:0',
                    'order' => 3,
                ],
                [
                    'question_code' => 'warranty_explained',
                    'question_text' => 'Warranty / claims procedure has been explained, including whom to contact in case of under-performance or downtime of equipment?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 4,
                ],
                [
                    'question_code' => 'warranty_contacts_attached',
                    'question_text' => 'Warranty / claims procedure and relevant contacts are attached to the fridge?',
                    'type' => 'yes_no',
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 5,
                ],
            ]);

            // Section 7: Documentation
            $section7 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 7 – Documentation',
                'description' => null,
                'order' => 7,
            ]);

            $this->createQuestions($section7, [
                [
                    'question_code' => 'documentation_language',
                    'question_text' => 'Language',
                    'type' => 'select',
                    'options' => ['English', 'French', 'Spanish', 'Portuguese', 'Local Language'],
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'user_manual_supplied',
                    'question_text' => 'User manual for all system components',
                    'type' => 'yes_no',
                    'required' => false,
                    'order' => 2,
                ],
                [
                    'question_code' => 'technician_manual_supplied',
                    'question_text' => 'Technician\'s manual',
                    'type' => 'yes_no',
                    'required' => false,
                    'order' => 3,
                ],
                [
                    'question_code' => 'installation_manual_supplied',
                    'question_text' => 'Installation manual',
                    'type' => 'yes_no',
                    'required' => false,
                    'order' => 4,
                ],
                [
                    'question_code' => 'documentation_comments',
                    'question_text' => 'Comments',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Enter any comments about documentation',
                    'order' => 5,
                ],
            ]);

            // Section 8: Overall Conclusions
            $section8 = ChecklistSection::create([
                'checklist_id' => $checklist->id,
                'title' => 'CHECK 8 – Overall Conclusions and Recommendations',
                'description' => null,
                'order' => 8,
            ]);

            $this->createQuestions($section8, [
                [
                    'question_code' => 'recommendation',
                    'question_text' => 'Recommendation',
                    'type' => 'select',
                    'options' => ['PASS', 'FAIL'],
                    'required' => true,
                    'validation_rules' => 'required',
                    'order' => 1,
                ],
                [
                    'question_code' => 'outstanding_work',
                    'question_text' => 'If FAIL, list outstanding work still required',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Describe outstanding work...',
                    'order' => 2,
                ],
            ]);

            $this->command->info('Checklist seeded successfully!');
            $this->command->info("Checklist: {$checklist->name} v{$checklist->version}");
            $this->command->info("Sections: {$checklist->sections->count()}");
            $this->command->info("Questions: {$checklist->total_questions_count}");
        });
    }

    /**
     * Helper method to create multiple questions for a section
     */
    private function createQuestions(ChecklistSection $section, array $questions): void
    {
        foreach ($questions as $questionData) {
            ChecklistQuestion::create(array_merge($questionData, [
                'checklist_section_id' => $section->id,
            ]));
        }
    }
}
