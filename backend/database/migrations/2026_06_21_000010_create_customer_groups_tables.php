<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $customerGroupsTable = Config::get('customer_groups.table_names.customer_groups', 'customer_groups');
        $pivotTable = Config::get('customer_groups.table_names.model_has_customer_groups', 'model_has_customer_groups');
        $pivotKey = Config::get('customer_groups.column_names.customer_group_pivot_key', 'customer_group_id') ?: 'customer_group_id';
        $modelMorphKey = Config::get('customer_groups.column_names.model_morph_key', 'model_id') ?: 'model_id';

        Schema::create($customerGroupsTable, function (Blueprint $table) use ($customerGroupsTable) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->enum('type', ['normal', 'vip', 'wholesale', 'agent', 'enterprise'])->default('normal');
            $table->integer('level')->default(1);
            $table->integer('discount_rate')->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->text('remark')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on($customerGroupsTable)->onDelete('set null');
            $table->foreign('market_id')->references('id')->on('markets')->onDelete('set null');
            $table->index('type');
            $table->index('is_active');
        });

        Schema::create($pivotTable, function (Blueprint $table) use ($customerGroupsTable, $pivotKey, $modelMorphKey) {
            $table->unsignedBigInteger($pivotKey);
            $table->string('model_type');
            $table->unsignedBigInteger($modelMorphKey);

            $table->foreign($pivotKey)->references('id')->on($customerGroupsTable)->onDelete('cascade');
            $table->primary([$pivotKey, 'model_type', $modelMorphKey]);
            $table->index(['model_type', $modelMorphKey]);
        });
    }

    public function down(): void
    {
        $pivotTable = Config::get('customer_groups.table_names.model_has_customer_groups', 'model_has_customer_groups');
        $customerGroupsTable = Config::get('customer_groups.table_names.customer_groups', 'customer_groups');

        Schema::dropIfExists($pivotTable);
        Schema::dropIfExists($customerGroupsTable);
    }
};
