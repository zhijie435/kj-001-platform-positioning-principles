<template>
    <div class="min-h-screen bg-gray-50 flex">
        <aside class="w-64 bg-slate-900 text-white flex flex-col">
            <div class="p-5 border-b border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-lg">Shearerline</div>
                        <div class="text-xs text-slate-400">供应链管理平台</div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-1">
                <router-link
                    v-for="item in menuItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
                    :class="$route.name === item.name ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <component :is="item.icon" class="w-5 h-5"/>
                    <span>{{ item.label }}</span>
                </router-link>
            </nav>

            <div class="p-3 border-t border-slate-700">
                <div class="flex items-center gap-3 px-3 py-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-medium">
                        {{ auth.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ auth.user?.name }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ roleLabel }}</div>
                    </div>
                    <button @click="handleLogout" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-colors" title="退出登录">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ $route.meta.title || pageTitle }}</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500">{{ auth.user?.email }}</span>
                    </div>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-auto">
                <router-view/>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed, h } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const IconDashboard = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' }),
        ]);
    },
};

const IconSupplier = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }),
        ]);
    },
};

const IconDistributor = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }),
        ]);
    },
};

const IconProduct = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' }),
        ]);
    },
};

const IconOrder = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }),
        ]);
    },
};

const IconCategory = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' }),
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

const IconPayment = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' }),
        ]);
    },
};

const IconUser = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }),
        ]);
    },
};

const allMenuItems = [
    { name: 'dashboard', label: '工作台', icon: IconDashboard, permission: null },
    { name: 'suppliers.index', label: '供应商管理', icon: IconSupplier, permission: 'supplier.view' },
    { name: 'distributors.index', label: '分销商管理', icon: IconDistributor, permission: 'distributor.view' },
    { name: 'products.index', label: '产品管理', icon: IconProduct, permission: 'product.view' },
    { name: 'orders.index', label: '订单管理', icon: IconOrder, permission: 'order.view' },
    { name: 'categories.index', label: '分类管理', icon: IconCategory, permission: null },
    { name: 'inventory.index', label: '库存管理', icon: IconInventory, permission: 'inventory.view' },
    { name: 'payments.index', label: '支付管理', icon: IconPayment, permission: 'payment.view' },
    { name: 'users.index', label: '用户管理', icon: IconUser, permission: 'user.manage' },
];

const menuItems = computed(() => allMenuItems.filter(item => !item.permission || auth.can(item.permission)));

const roleLabel = computed(() => {
    const roles = auth.roles;
    if (roles.includes('platform')) return '平台管理员';
    if (roles.includes('supplier')) return '供应商';
    if (roles.includes('regional_agent')) return '区域代理';
    if (roles.includes('distributor')) return '批发商';
    return '用户';
});

const pageTitle = computed(() => {
    const titles = {
        'dashboard': '工作台',
        'suppliers.index': '供应商管理',
        'distributors.index': '分销商管理',
        'products.index': '产品管理',
        'orders.index': '订单管理',
        'categories.index': '分类管理',
        'inventory.index': '库存管理',
        'payments.index': '支付管理',
        'users.index': '用户管理',
    };
    return titles[route.name] || '';
});

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'login' });
};
</script>
