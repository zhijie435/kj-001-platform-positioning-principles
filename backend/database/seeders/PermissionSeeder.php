<?php

namespace Database\Seeders;

use App\Models\Distributor;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = array_merge(
            [
                'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete', 'supplier.approve',
                'distributor.view', 'distributor.create', 'distributor.edit', 'distributor.delete', 'distributor.approve',
                'distributor.view.subordinate',
                'product.view', 'product.create', 'product.edit', 'product.delete', 'product.approve',
                'order.view', 'order.create', 'order.edit', 'order.delete', 'order.approve', 'order.ship',
                'order.view.subordinate',
                'payment.view', 'payment.create', 'payment.edit', 'payment.delete', 'payment.settle', 'payment.refund',
                'inventory.view', 'inventory.edit',
                'warehouse.view', 'warehouse.create', 'warehouse.edit', 'warehouse.delete',
                'report.view',
                'rule.manage',
                'user.manage',
            ]
        );

        foreach ($allPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $permissions = [
            'platform' => [
                'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete', 'supplier.approve',
                'distributor.view', 'distributor.create', 'distributor.edit', 'distributor.delete', 'distributor.approve',
                'product.view', 'product.approve',
                'order.view', 'order.approve',
                'payment.view', 'payment.settle', 'payment.refund',
                'inventory.view',
                'warehouse.view',
                'report.view',
                'rule.manage',
                'user.manage',
            ],
            'supplier' => [
                'product.view', 'product.create', 'product.edit',
                'order.view', 'order.ship',
                'inventory.view', 'inventory.edit',
                'warehouse.view', 'warehouse.create', 'warehouse.edit',
                'payment.view',
            ],
            'distributor' => [
                'product.view',
                'order.view', 'order.create',
                'payment.view', 'payment.create',
            ],
            'regional_agent' => [
                'product.view',
                'order.view', 'order.create',
                'order.view.subordinate',
                'payment.view',
                'distributor.view.subordinate',
            ],
        ];

        $platformRole = Role::firstOrCreate(['name' => 'platform', 'guard_name' => 'web']);
        $platformRole->syncPermissions($permissions['platform']);

        $supplierRole = Role::firstOrCreate(['name' => 'supplier', 'guard_name' => 'web']);
        $supplierRole->syncPermissions($permissions['supplier']);

        $distributorRole = Role::firstOrCreate(['name' => 'distributor', 'guard_name' => 'web']);
        $distributorRole->syncPermissions($permissions['distributor']);

        $agentRole = Role::firstOrCreate(['name' => 'regional_agent', 'guard_name' => 'web']);
        $agentRole->syncPermissions($permissions['regional_agent']);

        $supplier = Supplier::firstOrCreate(
            ['name' => '示例供应商'],
            [
                'company_name' => '示例供应商有限公司',
                'business_license' => '91110000MA00000001',
                'contact_person' => '张经理',
                'phone' => '13800000001',
                'email' => 'supplier@example.com',
                'address' => '北京市朝阳区供应商路1号',
                'bank_name' => '工商银行北京分行',
                'bank_account' => '6222000000000001',
                'credit_limit' => 100000.00,
                'balance' => 50000.00,
                'status' => 'active',
                'remark' => '示例供应商',
            ]
        );

        $agent = Distributor::firstOrCreate(
            ['name' => '华北区域代理'],
            [
                'company_name' => '华北区域代理有限公司',
                'business_license' => '91110000MA00000002',
                'type' => 'regional_agent',
                'region' => '华北',
                'contact_person' => '王总',
                'phone' => '13800000002',
                'email' => 'agent@example.com',
                'address' => '北京市海淀区代理路1号',
                'bank_name' => '建设银行北京分行',
                'bank_account' => '6227000000000002',
                'credit_limit' => 200000.00,
                'balance' => 80000.00,
                'discount_rate' => 90,
                'status' => 'active',
                'remark' => '华北区域总代理',
            ]
        );

        $distributor = Distributor::firstOrCreate(
            ['name' => '北京批发商'],
            [
                'company_name' => '北京批发商贸有限公司',
                'business_license' => '91110000MA00000003',
                'type' => 'wholesaler',
                'region' => '北京',
                'contact_person' => '李老板',
                'phone' => '13800000003',
                'email' => 'wholesaler@example.com',
                'address' => '北京市丰台区批发路1号',
                'bank_name' => '农业银行北京分行',
                'bank_account' => '6228000000000003',
                'credit_limit' => 50000.00,
                'balance' => 20000.00,
                'discount_rate' => 95,
                'status' => 'active',
                'parent_id' => $agent->id,
                'remark' => '北京地区批发商，隶属于华北代理',
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@shearerline.com'],
            [
                'name' => 'System Admin',
                'phone' => '13800000000',
                'user_type' => 'platform',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $admin->assignRole($platformRole);

        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@shearerline.com'],
            [
                'name' => '供应商管理员',
                'phone' => '13800000011',
                'user_type' => 'supplier',
                'supplier_id' => $supplier->id,
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $supplierUser->assignRole($supplierRole);

        $agentUser = User::firstOrCreate(
            ['email' => 'agent@shearerline.com'],
            [
                'name' => '区域代理管理员',
                'phone' => '13800000022',
                'user_type' => 'distributor',
                'distributor_id' => $agent->id,
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $agentUser->assignRole($agentRole);

        $distributorUser = User::firstOrCreate(
            ['email' => 'distributor@shearerline.com'],
            [
                'name' => '批发商管理员',
                'phone' => '13800000033',
                'user_type' => 'distributor',
                'distributor_id' => $distributor->id,
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]
        );
        $distributorUser->assignRole($distributorRole);
    }
}
