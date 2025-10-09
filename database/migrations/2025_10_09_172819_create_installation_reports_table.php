<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('installation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('health_officer_id')->nullable()->constrained('health_officers')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'verified', 'rejected'])->default('draft');
            $table->text('comments')->nullable();
            $table->json('signatures')->nullable(); // base64 or file paths
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('installation_reports');
    }
};
