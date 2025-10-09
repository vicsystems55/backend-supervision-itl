<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_lead_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->date('assigned_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'verified'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_technician_assignments');
    }
};
