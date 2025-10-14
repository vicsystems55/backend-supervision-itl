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
        Schema::create('installation_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'submitted', 'verified'])->default('draft');
            $table->integer('progress_percentage')->default(0);

            // Header information (common to all checklists)
            $table->date('checklist_date');
            $table->string('installation_technician');
            $table->string('installation_company')->default('Inter-Trade Ltd.');
            $table->string('technician_signature');
            $table->string('health_center_signature');
            $table->string('health_center_name');
            $table->date('completion_date');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['installation_id', 'checklist_id'], 'ic_install_checklist_index');
            $table->index('status', 'ic_status_index');
            $table->index('created_at', 'ic_created_at_index');
                    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_checklists');
    }
};
