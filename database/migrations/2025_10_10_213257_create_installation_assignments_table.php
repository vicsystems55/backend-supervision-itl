<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('installation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('installation_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('assigned');
            $table->timestamps();

            $table->unique(['user_id', 'installation_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('installation_assignments');
    }
};
