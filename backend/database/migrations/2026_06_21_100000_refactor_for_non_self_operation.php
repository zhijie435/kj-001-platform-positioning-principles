<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'fee_amount')) {
                $table->decimal('fee_amount', 15, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 20)->default('completed')->after('transaction_no');
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('market_id');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            }
        });

        try {
            DB::table('payments')->where('type', 'income')->update(['type' => 'escrow_deposit']);
            DB::table('payments')->where('type', 'expense')->update(['type' => 'escrow_release']);
        } catch (\Exception $e) {
        }
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('payments', 'fee_amount')) {
                $table->dropColumn('fee_amount');
            }
        });

        try {
            DB::table('payments')->where('type', 'escrow_deposit')->update(['type' => 'income']);
            DB::table('payments')->where('type', 'escrow_release')->update(['type' => 'expense']);
            DB::table('payments')->whereIn('type', ['platform_fee', 'refund'])->update(['type' => 'income']);
        } catch (\Exception $e) {
        }
    }
};
