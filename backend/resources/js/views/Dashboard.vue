<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="bg-white rounded-xl p-5 border border-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">{{ stat.label }}</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stat.value }}</div>
                    </div>
                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', stat.bgColor]">
                        <component :is="stat.icon" :class="['w-6 h-6', stat.iconColor]"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">系统信息</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">当前版本</span>
                        <span class="font-medium text-gray-900">Shearerline v1.0.0</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">当前角色</span>
                        <span class="font-medium text-gray-900">{{ roleLabel }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-50">
                        <span class="text-gray-500">账号类型</span>
                        <span class="font-medium text-gray-900">{{ userTypeLabel }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500">可用权限数</span>
                        <span class="font-medium text-indigo-600">{{ auth.permissions?.length || 0 }} 项</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">快捷入口</h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-3">
                    <router-link
                        v-for="action in quickActions"
                        :key="action.name"
                        :to="{ name: action.name }"
                        class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                    >
                        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', action.bgColor]">
                            <component :is="action.icon" :class="['w-5 h-5', action.iconColor]"/>
                        </div>
                        <span class="font-medium text-gray-900">{{ action.label }}</span>
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();

const IconSuppliers = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }),
        ]);
    },
};

const IconDistributors = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }),
        ]);
    },
};

const IconProducts = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' }),
        ]);
    },
};

const IconOrders = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }),
        ]);
    },
};

const IconUsers = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }),
        ]);
    },
};

const IconInventory = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' }),
        ]);
    },
};

const stats = [
    { label: '供应商', value: '1', icon: IconSuppliers, bgColor: 'bg-blue-50', iconColor: 'text-blue-600' },
    { label: '分销商', value: '2', icon: IconDistributors, bgColor: 'bg-green-50', iconColor: 'text-green-600' },
    { label: '产品数量', value: '0', icon: IconProducts, bgColor: 'bg-amber-50', iconColor: 'text-amber-600' },
    { label: '订单总数', value: '0', icon: IconOrders, bgColor: 'bg-purple-50', iconColor: 'text-purple-600' },
];

const allActions = [
    { name: 'suppliers.index', label: '供应商', icon: IconSuppliers, bgColor: 'bg-blue-50', iconColor: 'text-blue-600', permission: 'supplier.view' },
    { name: 'distributors.index', label: '分销商', icon: IconDistributors, bgColor: 'bg-green-50', iconColor: 'text-green-600', permission: 'distributor.view' },
    { name: 'products.index', label: '产品管理', icon: IconProducts, bgColor: 'bg-amber-50', iconColor: 'text-amber-600', permission: 'product.view' },
    { name: 'orders.index', label: '订单管理', icon: IconOrders, bgColor: 'bg-purple-50', iconColor: 'text-purple-600', permission: 'order.view' },
    { name: 'inventory.index', label: '库存管理', icon: IconInventory, bgColor: 'bg-rose-50', iconColor: 'text-rose-600', permission: 'inventory.view' },
    { name: 'users.index', label: '用户管理', icon: IconUsers, bgColor: 'bg-indigo-50', iconColor: 'text-indigo-600', permission: 'user.manage' },
];

const quickActions = computed(() => allActions.filter(a => !a.permission || auth.can(a.permission)));

const roleLabel = computed(() => {
    const roles = auth.roles;
    if (roles.includes('platform')) return '平台管理员';
    if (roles.includes('supplier')) return '供应商';
    if (roles.includes('regional_agent')) return '区域代理';
    if (roles.includes('distributor')) return '批发商';
    return '用户';
});

const userTypeLabel = computed(() => {
    const types = { platform: '平台端', supplier: '供应商端', distributor: '分销商端' };
    return types[auth.userType] || '-';
});
</script>
