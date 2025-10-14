<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checklist_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_section_id')->constrained()->cascadeOnDelete();
            $table->string('question_code')->unique(); // e.g., "panel_orientation_correct"
            $table->text('question_text'); // e.g., "Has the panel been installed at the correct angle towards the equator?"
            $table->enum('type', ['yes_no', 'text', 'number', 'select', 'textarea', 'date', 'signature'])->default('yes_no');
            $table->json('options')->nullable(); // For select fields: ["Yes", "No", "Not Applicable"]
            $table->boolean('required')->default(true);
            $table->string('validation_rules')->nullable(); // e.g., "required|min:1"
            $table->string('placeholder')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes for better performance
            $table->index(['checklist_section_id', 'order'], 'cq_section_order_index');
            $table->index('question_code', 'cq_question_code_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_questions');
    }
};
