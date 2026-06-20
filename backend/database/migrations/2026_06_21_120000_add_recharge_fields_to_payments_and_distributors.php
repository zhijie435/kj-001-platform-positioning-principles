<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'distributor_id')) {
                $table->unsignedBigInteger('distributor_id')->nullable()->after('order_id');
                $table->foreign('distributor_id')->references('id')->on('distributors')->onDelete('set null');
            }
            if (!Schema::hasColumn('payments', 'fail_reason')) {
                $table->string('fail_reason')->nullable()->after('remark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'fail_reason')) {
                $table->dropColumn('fail_reason');
            }
            if (Schema::hasColumn('payments', 'distributor_id')) {
                $table->dropForeign(['distributor_id']);
                $table->dropColumn('distributor_id');
            }
        });
    }
};
