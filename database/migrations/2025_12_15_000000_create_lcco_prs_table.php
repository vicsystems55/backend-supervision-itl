<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lcco_prs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained('installations')->onDelete('cascade');
            $table->string('lcco_name');
            $table->string('lcco_phone')->nullable();
            $table->string('device_tag_code')->nullable();
            $table->string('device_serial_number')->nullable();
            $table->string('installation_status')->nullable();
            $table->string('lcco_account_number')->nullable();
            $table->string('lcco_bank_name')->nullable();
            $table->string('lcco_account_name')->nullable();
            $table->string('payment_status')->nullable()->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lcco_prs');
    }
};
