<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->enum('type', ['vat', 'gst', 'sales_tax', 'duty', 'ipi', 'icms', 'pis', 'cofins', 'other'])->default('vat');
            $table->string('name');
            $table->decimal('rate', 10, 4)->default(0);
            $table->decimal('min_amount', 15, 2)->nullable();
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->boolean('is_compound')->default(false);
            $table->json('compound_rules')->nullable();
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('market_id')->references('id')->on('markets');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
