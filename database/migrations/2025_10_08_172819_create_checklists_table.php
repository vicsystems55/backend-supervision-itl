<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Installation Checklist');
            $table->string('reference_code')->nullable(); // e.g. Annex E
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('checklists');
    }
};
