<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('created_by');
            $table->enum('type', ['income', 'expense']);
            $table->enum('method', ['cash', 'bank_transfer', 'alipay', 'wechat', 'credit', 'other']);
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('CNY');
            $table->date('payment_date');
            $table->string('transaction_no')->nullable();
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
