<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->foreignId('lga_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('facility_type')->nullable();
            $table->string('supply_chain_level');
            $table->boolean('road_accessible')->default(true);
            $table->integer('distance_from_hub_km')->default(0);
            $table->enum('road_quality', ['Fully Paved', 'Partially Paved', 'Dirt Road (Good Quality)', 'Dirt Road (Rough)'])->nullable();
            $table->timestamps();

            $table->index(['state_id', 'lga_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
