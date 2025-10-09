<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. Solar Panel, Inverter, Battery
            $table->string('model')->nullable(); // e.g. HTD-400W
            $table->string('serial_number')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable(); // e.g. pcs, sets, boxes
            $table->enum('status', ['available', 'in_transit', 'installed', 'damaged'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessories');
    }
};
