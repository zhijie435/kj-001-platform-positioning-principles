<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customs_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('declaration_no')->unique();
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->enum('type', ['import', 'export', 'transit'])->default('import');
            $table->enum('status', ['pending', 'declared', 'inspecting', 'released', 'rejected', 'appealing'])->default('pending');
            $table->string('declarant')->nullable();
            $table->date('declaration_date')->nullable();
            $table->date('release_date')->nullable();
            $table->text('hs_code_summary')->nullable();
            $table->decimal('declared_value', 15, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('duty_amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_fee', 15, 2)->default(0);
            $table->string('customs_broker')->nullable();
            $table->json('documents')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index('status');
            $table->index('type');
        });

        Schema::create('customs_declaration_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customs_declaration_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->string('hs_code', 50)->nullable();
            $table->string('country_of_origin', 10)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('unit')->nullable();
            $table->decimal('unit_value', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->decimal('weight_per_unit', 15, 3)->nullable();
            $table->decimal('gross_weight', 15, 3)->nullable();
            $table->decimal('net_weight', 15, 3)->nullable();
            $table->decimal('duty_rate', 10, 4)->default(0);
            $table->decimal('duty_amount', 15, 2)->default(0);
            $table->decimal('vat_rate', 10, 4)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->foreign('customs_declaration_id')->references('id')->on('customs_declarations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customs_declaration_items');
        Schema::dropIfExists('customs_declarations');
    }
};
