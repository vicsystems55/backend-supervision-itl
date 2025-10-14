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
        Schema::create('checklist_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // e.g., "CHECK 3 – Solar Panel Installation"
            $table->text('description')->nullable(); // e.g., "Note: The technician must get a good orientation..."
            $table->integer('order')->default(0);
            $table->timestamps();

            // Index for better performance
            $table->index(['checklist_id', 'order'], 'cs_checklist_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_sections');
    }
};
