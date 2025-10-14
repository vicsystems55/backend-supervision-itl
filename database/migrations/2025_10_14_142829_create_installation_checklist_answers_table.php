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
        Schema::create('installation_checklist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_checklist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();

            // Unique constraint with custom shorter name
            $table->unique(
                ['installation_checklist_id', 'checklist_question_id'],
                'install_checklist_answers_unique'
            );

            // Indexes with custom names
            $table->index('installation_checklist_id', 'ica_checklist_id_index');
            $table->index('checklist_question_id', 'ica_question_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_checklist_answers');
    }
};
