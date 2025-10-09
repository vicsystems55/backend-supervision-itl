<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete(); // team lead or primary installer
            $table->foreignId('delivery_id')->nullable()->constrained()->nullOnDelete();

            // Annex G fields
            $table->string('country')->default('Nigeria');
            $table->string('province')->nullable(); // e.g. State
            $table->string('supplier')->nullable();
            $table->string('po_number')->nullable(); // Purchase Order Number
            $table->string('po_item_number')->nullable();
            $table->string('service_contract_number')->nullable();
            $table->string('product_model')->nullable();

            // Quantities
            $table->integer('total_quantity_received')->nullable(); // after customs clearance
            $table->date('date_received_in_country')->nullable();
            $table->integer('total_quantity_delivered')->nullable();
            $table->integer('total_quantity_installed')->nullable();

            // Installation progress
            $table->date('planned_installation_end_date')->nullable();
            $table->date('actual_installation_end_date')->nullable();

            // Deviations, remarks, and supplier comments
            $table->integer('number_of_deviations')->default(0);
            $table->text('supplier_comments')->nullable();
            $table->text('remarks')->nullable();

            // Approval and verification
            $table->boolean('verified_by_health_officer')->default(false);
            $table->foreignId('health_officer_id')->nullable()->constrained('health_officers')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
