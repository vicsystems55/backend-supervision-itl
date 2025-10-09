<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete(); // Belongs to a shipment
            $table->string('name'); // e.g., HTD-40 SDD
            $table->string('model')->nullable(); // Optional additional model identifier
            $table->string('serial_number')->nullable(); // Unique serial
            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable(); // e.g. pcs, sets
            $table->enum('status', [
                'available',   // In warehouse
                'in_transit',  // On the way to a site
                'installed',   // Deployed at a facility
                'faulty',      // Found defective
                'repaired'     // After maintenance
            ])->default('available');
            $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete(); // Where installed
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
