<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->unsignedBigInteger('market_id')->nullable()->after('parent_id');
            $table->string('country_code', 10)->nullable()->after('market_id');
            $table->string('tax_id')->nullable()->after('country_code');
            $table->string('local_business_license')->nullable()->after('tax_id');
            $table->string('import_export_code')->nullable()->after('local_business_license');
            $table->json('serviced_states')->nullable()->after('import_export_code');
            $table->json('payment_terms')->nullable()->after('serviced_states');
            $table->json('shipping_preferences')->nullable()->after('payment_terms');
            $table->boolean('is_cross_border')->default(false)->after('shipping_preferences');
            $table->json('certifications')->nullable()->after('is_cross_border');

            $table->foreign('market_id')->references('id')->on('markets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropForeign(['market_id']);
            $table->dropColumn([
                'market_id', 'country_code', 'tax_id', 'local_business_license',
                'import_export_code', 'serviced_states', 'payment_terms',
                'shipping_preferences', 'is_cross_border', 'certifications',
            ]);
        });
    }
};
