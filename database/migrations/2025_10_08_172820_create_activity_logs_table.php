<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // What action occurred
            $table->string('activity_type')->nullable(); // e.g. "shipment_dispatched", "installation_completed"
            $table->text('description')->nullable();

            // Link to related model (polymorphic)
            $table->morphs('subject');
            // subject_type: model class, subject_id: model record

            // Optional context data
            $table->json('metadata')->nullable(); // e.g. {"ip": "...", "lat": ..., "lng": ...}

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
