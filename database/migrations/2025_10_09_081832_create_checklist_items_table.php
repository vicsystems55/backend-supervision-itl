<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->cascadeOnDelete();
            $table->string('section')->nullable(); // e.g. “Check 3 – Solar Panel Installation”
            $table->string('question'); // e.g. “Has the lightning protection circuit been correctly fitted?”
            $table->enum('type', ['yes_no', 'text', 'number', 'select'])->default('yes_no');
            $table->boolean('required')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('checklist_items');
    }
};
