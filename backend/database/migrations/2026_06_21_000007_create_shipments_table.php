<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_no')->unique();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('carrier')->nullable();
            $table->unsignedBigInteger('origin_warehouse_id')->nullable();
            $table->unsignedBigInteger('destination_warehouse_id')->nullable();
            $table->unsignedBigInteger('origin_market_id')->nullable();
            $table->unsignedBigInteger('destination_market_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('sender_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_city')->nullable();
            $table->string('receiver_state')->nullable();
            $table->string('receiver_postal_code', 50)->nullable();
            $table->string('receiver_country', 10)->nullable();
            $table->decimal('weight', 15, 3)->nullable();
            $table->decimal('volume', 15, 3)->nullable();
            $table->integer('packages')->default(1);
            $table->decimal('declared_value', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('insurance_cost', 15, 2)->default(0);
            $table->decimal('fuel_surcharge', 15, 2)->default(0);
            $table->decimal('other_fee', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('status', ['pending', 'picked_up', 'shipped', 'in_transit', 'customs', 'out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'])->default('pending');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('in_transit_at')->nullable();
            $table->dateTime('customs_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->json('tracking_history')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('set null');
            $table->foreign('origin_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('destination_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('origin_market_id')->references('id')->on('markets')->onDelete('set null');
            $table->foreign('destination_market_id')->references('id')->on('markets')->onDelete('set null');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
