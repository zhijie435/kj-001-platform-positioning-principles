<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('business_license')->nullable();
            $table->enum('type', ['regional_agent', 'wholesaler'])->default('wholesaler');
            $table->string('region')->nullable();
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->unsignedInteger('discount_rate')->default(100);
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('distributors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributors');
    }
};
