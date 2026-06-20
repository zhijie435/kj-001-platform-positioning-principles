<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('carrier')->nullable();
            $table->unsignedBigInteger('origin_market_id')->nullable();
            $table->unsignedBigInteger('destination_market_id')->nullable();
            $table->enum('type', ['air', 'sea', 'express', 'land', 'rail'])->default('air');
            $table->integer('min_days')->nullable();
            $table->integer('max_days')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('price_per_kg', 15, 2)->default(0);
            $table->decimal('price_per_cbm', 15, 2)->default(0);
            $table->decimal('fuel_surcharge_rate', 10, 4)->default(0);
            $table->boolean('is_trackable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('origin_market_id')->references('id')->on('markets')->onDelete('set null');
            $table->foreign('destination_market_id')->references('id')->on('markets')->onDelete('set null');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
