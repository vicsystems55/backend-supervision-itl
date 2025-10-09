<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique(); // vehicle registration
            $table->string('model')->nullable();      // e.g., MAN TGS 18.440
            $table->string('capacity')->nullable();   // e.g., "10 Tons"
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // optional: assigned system user

            // Tracking info
            $table->string('current_location')->nullable(); // state or city
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();

            // Truck status
            $table->enum('status', [
                'available',
                'in_transit',
                'under_maintenance',
                'unavailable'
            ])->default('available');

            $table->timestamp('last_maintenance_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
