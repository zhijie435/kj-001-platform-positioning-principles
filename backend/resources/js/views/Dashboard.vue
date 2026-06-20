<template>
    <div class="space-y-6">
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="text-gray-500">加载中...</div>
        </div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="stat in displayStats"
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

            <div v-if="isPlatform && dashboardData.escrow" class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">资金监管总览</h3>
                    <p class="text-xs text-gray-500 mt-1">平台作为中立第三方，对交易资金进行全程托管监管</p>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="p-4 rounded-lg bg-blue-50">
                        <div class="text-xs text-blue-600">累计托管充值</div>
                        <div class="text-xl font-bold text-blue-700 mt-1">¥{{ formatNumber(dashboardData.escrow.total_deposited) }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-green-50">
                        <div class="text-xs text-green-600">已结算给供应商</div>
                        <div class="text-xl font-bold text-green-700 mt-1">¥{{ formatNumber(dashboardData.escrow.total_released) }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-rose-50">
                        <div class="text-xs text-rose-600">已退款</div>
                        <div class="text-xl font-bold text-rose-700 mt-1">¥{{ formatNumber(dashboardData.escrow.total_refunded) }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-indigo-50">
                        <div class="text-xs text-indigo-600">当前托管余额</div>
                        <div class="text-xl font-bold text-indigo-700 mt-1">¥{{ formatNumber(dashboardData.escrow.current_balance) }}</div>
                    </div>
                    <div class="p-4 rounded-lg bg-amber-50">
                        <div class="text-xs text-amber-600">累计平台服务费</div>
                        <div class="text-xl font-bold text-amber-700 mt-1">¥{{ formatNumber(dashboardData.escrow.platform_fees) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="dashboardData.gmv" class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">平台交易总额 (GMV)</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">总交易额</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">¥{{ formatNumber(dashboardData.gmv.total) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">已支付金额</div>
                        <div class="text-2xl font-bold text-green-600 mt-1">¥{{ formatNumber(dashboardData.gmv.paid) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="dashboardData.revenue" class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">营业数据</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">订单总额</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">¥{{ formatNumber(dashboardData.revenue.total) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">已收款</div>
                        <div class="text-2xl font-bold text-green-600 mt-1">¥{{ formatNumber(dashboardData.revenue.paid) }}</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div v-if="dashboardData.order_stats" class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">订单状态分布</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <div v-for="(count, status) in dashboardData.order_stats" :key="status" class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                            <span class="text-gray-600">{{ orderStatusLabel(status) }}</span>
                            <span class="font-semibold text-gray-900">{{ count }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">平台定位</h3>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">流</div>
                            <div>
                                <div class="font-medium text-blue-900">流量运营</div>
                                <div class="text-blue-700 text-xs mt-0.5">为供应商和分销商提供交易撮合与流量对接</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-green-50">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center text-green-600 font-bold text-sm">规</div>
                            <div>
                                <div class="font-medium text-green-900">规则制定</div>
                                <div class="text-green-700 text-xs mt-0.5">制定并执行平台交易规则，保障公平交易环境</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-3 rounded-lg bg-amber-50">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 font-bold text-sm">资</div>
                            <div>
                                <div class="font-medium text-amber-900">资金监管</div>
                                <div class="text-amber-700 text-xs mt-0.5">第三方托管交易资金，确认收货后结算给供应商</div>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-gray-50 text-gray-600 text-xs">
                            <span class="font-semibold text-gray-700">平台承诺：</span>不直接参与买卖，不自营产品，不与商家争利。
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="dashboardData.recent_orders?.length" class="bg-white rounded-xl border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">最近订单</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium">订单号</th>
                                <th v-if="isPlatform" class="px-6 py-3 text-left font-medium">供应商</th>
                                <th class="px-6 py-3 text-left font-medium">分销商</th>
                                <th class="px-6 py-3 text-left font-medium">金额</th>
                                <th class="px-6 py-3 text-left font-medium">状态</th>
                                <th class="px-6 py-3 text-left font-medium">时间</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in dashboardData.recent_orders" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-mono text-xs text-gray-900">{{ order.order_no }}</td>
                                <td v-if="isPlatform" class="px-6 py-3 text-gray-700">{{ order.supplier?.name || '-' }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ order.distributor?.name || '-' }}</td>
                                <td class="px-6 py-3 font-medium text-gray-900">¥{{ formatNumber(order.total) }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex px-2 py-0.5 text-xs rounded-full" :class="orderStatusClass(order.status)">
                                        {{ orderStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-500 text-xs">{{ order.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const auth = useAuthStore();

const loading = ref(true);
const dashboardData = ref({});

const isPlatform = computed(() => auth.hasRole('platform'));
const isSupplier = computed(() => auth.hasRole('supplier'));

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

const IconEscrow = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' }),
        ]);
    },
};

const IconGMV = {
    render() {
        return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' }),
        ]);
    },
};

const displayStats = computed(() => {
    const data = dashboardData.value;
    if (!data.counts) return [];

    const counts = data.counts;
    if (isPlatform.value) {
        return [
            { label: '入驻供应商', value: counts.suppliers ?? 0, icon: IconSuppliers, bgColor: 'bg-blue-50', iconColor: 'text-blue-600' },
            { label: '入驻分销商', value: counts.distributors ?? 0, icon: IconDistributors, bgColor: 'bg-green-50', iconColor: 'text-green-600' },
            { label: '平台GMV', value: '¥' + formatNumber(data.gmv?.total ?? 0), icon: IconGMV, bgColor: 'bg-purple-50', iconColor: 'text-purple-600' },
            { label: '托管余额', value: '¥' + formatNumber(data.escrow?.current_balance ?? 0), icon: IconEscrow, bgColor: 'bg-indigo-50', iconColor: 'text-indigo-600' },
        ];
    }
    if (isSupplier.value) {
        return [
            { label: '产品数量', value: counts.products ?? 0, icon: IconProducts, bgColor: 'bg-amber-50', iconColor: 'text-amber-600' },
            { label: '订单总数', value: counts.orders ?? 0, icon: IconOrders, bgColor: 'bg-purple-50', iconColor: 'text-purple-600' },
            { label: '客户数量', value: counts.customers ?? 0, icon: IconDistributors, bgColor: 'bg-green-50', iconColor: 'text-green-600' },
            { label: '应收总额', value: '¥' + formatNumber(data.revenue?.total ?? 0), icon: IconGMV, bgColor: 'bg-indigo-50', iconColor: 'text-indigo-600' },
        ];
    }
    return [
        { label: '产品数量', value: counts.products ?? 0, icon: IconProducts, bgColor: 'bg-amber-50', iconColor: 'text-amber-600' },
        { label: '订单总数', value: counts.orders ?? 0, icon: IconOrders, bgColor: 'bg-purple-50', iconColor: 'text-purple-600' },
        { label: '上级供应商', value: counts.suppliers ?? 0, icon: IconSuppliers, bgColor: 'bg-blue-50', iconColor: 'text-blue-600' },
        { label: '采购总额', value: '¥' + formatNumber(data.revenue?.total ?? 0), icon: IconGMV, bgColor: 'bg-indigo-50', iconColor: 'text-indigo-600' },
    ];
});

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00';
    return Number(num).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const orderStatusLabel = (status) => {
    const labels = {
        draft: '草稿',
        pending: '待确认',
        confirmed: '已确认',
        processing: '处理中',
        shipped: '已发货',
        delivered: '已送达',
        completed: '已完成',
        cancelled: '已取消',
        unpaid: '未付款',
        partial: '部分付款',
        paid: '已付款',
    };
    return labels[status] || status;
};

const orderStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        pending: 'bg-amber-100 text-amber-700',
        confirmed: 'bg-blue-100 text-blue-700',
        processing: 'bg-indigo-100 text-indigo-700',
        shipped: 'bg-purple-100 text-purple-700',
        delivered: 'bg-cyan-100 text-cyan-700',
        completed: 'bg-green-100 text-green-700',
        cancelled: 'bg-rose-100 text-rose-700',
        unpaid: 'bg-rose-100 text-rose-700',
        partial: 'bg-amber-100 text-amber-700',
        paid: 'bg-green-100 text-green-700',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

onMounted(async () => {
    try {
        const { data } = await api.get('/dashboard');
        dashboardData.value = data.data || data;
    } catch (e) {
        console.error('加载Dashboard失败:', e);
    } finally {
        loading.value = false;
    }
});
</script>
