<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('user_type', ['platform', 'supplier', 'distributor'])->default('platform')->after('avatar');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('user_type');
            $table->unsignedBigInteger('distributor_id')->nullable()->after('supplier_id');
            $table->boolean('is_active')->default(true)->after('distributor_id');
            $table->softDeletes();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('distributor_id')->references('id')->on('distributors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['distributor_id']);
            $table->dropColumn(['phone', 'avatar', 'user_type', 'supplier_id', 'distributor_id', 'is_active', 'deleted_at']);
        });
    }
};
