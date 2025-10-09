<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->timestamp('recorded_at')->useCurrent();
            $table->decimal('speed', 8, 2)->nullable(); // in km/h
            $table->decimal('fuel_level', 8, 2)->nullable(); // percentage or liters
            $table->string('status')->nullable(); // e.g. enroute, stopped, delayed

            $table->json('meta')->nullable(); // any additional data (e.g., GPS accuracy, battery level)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_routes');
    }
};
