<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_market_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('market_id');
            $table->string('currency', 10);
            $table->string('local_name')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('wholesale_price', 15, 2)->default(0);
            $table->decimal('agent_price', 15, 2)->default(0);
            $table->decimal('retail_price', 15, 2)->default(0);
            $table->integer('min_order_qty')->nullable();
            $table->integer('max_order_qty')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('market_id')->references('id')->on('markets');
            $table->unique(['product_id', 'market_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_market_prices');
    }
};
