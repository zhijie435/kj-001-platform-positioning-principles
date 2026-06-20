<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('country_code', 10);
            $table->string('currency_code', 10);
            $table->string('currency_symbol', 10)->nullable();
            $table->string('language_code', 10)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('flag')->nullable();
            $table->decimal('tax_rate', 10, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('country_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
