<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 10);
            $table->string('target_currency', 10);
            $table->decimal('rate', 15, 6);
            $table->decimal('buy_rate', 15, 6)->nullable();
            $table->decimal('sell_rate', 15, 6)->nullable();
            $table->string('source', 100)->nullable();
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['base_currency', 'target_currency']);
            $table->index('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
