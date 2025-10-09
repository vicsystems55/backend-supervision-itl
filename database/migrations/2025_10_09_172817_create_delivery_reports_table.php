<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('health_officer_id')->nullable()->constrained('health_officers')->nullOnDelete();

            $table->string('report_status')->default('pending');
            // pending, received, verified, rejected

            $table->timestamp('report_date')->nullable();

            $table->text('remarks')->nullable();
            $table->json('proof_images')->nullable(); // proof of delivery (signatures, photos)

            $table->unsignedTinyInteger('condition_rating')->nullable();
            // 1–5 scale (1 poor, 5 excellent)

            $table->boolean('signed_off')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_reports');
    }
};
