<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('country_code', 10)->nullable()->after('status');
            $table->string('tax_id')->nullable()->after('country_code');
            $table->string('export_license')->nullable()->after('tax_id');
            $table->string('import_export_code')->nullable()->after('export_license');
            $table->json('certifications')->nullable()->after('import_export_code');
            $table->json('serviced_markets')->nullable()->after('certifications');
            $table->boolean('is_cross_border')->default(false)->after('serviced_markets');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('hs_code', 50)->nullable()->after('status');
            $table->string('country_of_origin', 10)->nullable()->after('hs_code');
            $table->decimal('weight', 15, 3)->nullable()->after('country_of_origin');
            $table->decimal('volume', 15, 3)->nullable()->after('weight');
            $table->boolean('is_cross_border')->default(false)->after('volume');
            $table->string('material')->nullable()->after('is_cross_border');
            $table->string('brand')->nullable()->after('material');
            $table->json('certifications')->nullable()->after('brand');
            $table->text('customs_description')->nullable()->after('certifications');
            $table->json('local_names')->nullable()->after('customs_description');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('market_id')->nullable()->after('remark');
            $table->string('currency', 10)->nullable()->after('market_id');
            $table->decimal('exchange_rate', 15, 6)->nullable()->after('currency');
            $table->boolean('is_cross_border')->default(false)->after('exchange_rate');
            $table->string('incoterms', 10)->nullable()->after('is_cross_border');
            $table->decimal('insurance_fee', 15, 2)->default(0)->after('incoterms');
            $table->decimal('duty_fee', 15, 2)->default(0)->after('insurance_fee');
            $table->decimal('vat_fee', 15, 2)->default(0)->after('duty_fee');
            $table->decimal('customs_fee', 15, 2)->default(0)->after('vat_fee');
            $table->decimal('other_fee', 15, 2)->default(0)->after('customs_fee');

            $table->foreign('market_id')->references('id')->on('markets')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 10)->nullable()->after('is_active');
            $table->string('language', 10)->nullable()->after('country_code');
            $table->string('timezone', 50)->nullable()->after('language');
            $table->json('accessible_markets')->nullable()->after('timezone');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('supplier_id');

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'country_code', 'language', 'timezone', 'accessible_markets',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->dropColumn([
                'market_id', 'currency', 'exchange_rate', 'is_cross_border',
                'incoterms', 'insurance_fee', 'duty_fee', 'vat_fee',
                'customs_fee', 'other_fee',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'hs_code', 'country_of_origin', 'weight', 'volume',
                'is_cross_border', 'material', 'brand', 'certifications',
                'customs_description', 'local_names',
            ]);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'country_code', 'tax_id', 'export_license', 'import_export_code',
                'certifications', 'serviced_markets', 'is_cross_border',
            ]);
        });
    }
};
