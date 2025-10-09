<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('reference_number')->unique(); // e.g., SHP-2025-001
            $table->string('batch_name')->nullable(); // e.g., HTD-40 Batch A
            $table->integer('total_items')->default(0);
            $table->decimal('total_weight', 10, 2)->nullable();

            // Relationships
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // who created the shipment
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // approved for dispatch

            // Location and destination tracking
            $table->string('origin_state')->default('Lagos');
            $table->string('destination_state')->nullable();
            $table->string('destination_lga')->nullable();

            // Timestamps for movement
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Status tracking
            $table->enum('status', [
                'pending',       // not yet dispatched
                'in_transit',    // left warehouse
                'arrived',       // arrived at state
                'verified',      // health officer confirmed
                'distributed',   // equipment sent to LGAs/facilities
                'completed'      // fully installed
            ])->default('pending');

            $table->text('remarks')->nullable(); // comments from warehouse, driver, or health officer

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
