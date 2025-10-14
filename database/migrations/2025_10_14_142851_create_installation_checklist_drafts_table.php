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
        Schema::create('installation_checklist_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->json('form_data');
            $table->integer('progress_percentage')->default(0);
            $table->string('last_saved_section')->nullable();
            $table->timestamp('last_saved_at');
            $table->timestamps();

            // Unique constraint with custom shorter name
            $table->unique(
                ['installation_id', 'checklist_id'],
                'install_checklist_drafts_unique'
            );

            // Indexes with custom names
            $table->index('installation_id', 'icd_install_id_index');
            $table->index('checklist_id', 'icd_checklist_id_index');
            $table->index('last_saved_at', 'icd_last_saved_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_checklist_drafts');
    }
};
