<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="搜索用户名、邮箱、手机号..."
                        class="w-80 pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                    />
                    <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    v-model="filterType"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部类型</option>
                    <option value="platform">平台端</option>
                    <option value="supplier">供应商端</option>
                    <option value="distributor">分销商端</option>
                </select>
                <select
                    v-model="filterStatus"
                    class="px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white"
                >
                    <option value="">全部状态</option>
                    <option value="active">已启用</option>
                    <option value="inactive">已禁用</option>
                </select>
            </div>
            <button
                @click="openCreateModal"
                class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                新建用户
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">用户</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">类型</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">关联主体</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">角色</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">状态</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">创建时间</th>
                        <th class="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="loading" class="text-center">
                        <td colspan="7" class="px-6 py-12 text-gray-500">加载中...</td>
                    </tr>
                    <tr v-else-if="!users.length" class="text-center">
                        <td colspan="7" class="px-6 py-12 text-gray-500">暂无数据</td>
                    </tr>
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-medium text-sm">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 text-sm">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span :class="userTypeBadge(user.user_type)">
                                {{ userTypeLabel(user.user_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ user.supplier?.name || user.distributor?.name || '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="role in user.roles"
                                    :key="role"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                                >
                                    {{ roleLabel(role) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <button @click="toggleStatus(user)" class="inline-flex items-center gap-1.5">
                                <span :class="['w-2 h-2 rounded-full', user.is_active ? 'bg-green-500' : 'bg-gray-300']"/>
                                <span :class="['text-sm', user.is_active ? 'text-gray-700' : 'text-gray-500']">
                                    {{ user.is_active ? '已启用' : '已禁用' }}
                                </span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="openEditModal(user)"
                                    class="text-sm text-indigo-600 hover:text-indigo-700"
                                >
                                    编辑
                                </button>
                                <button
                                    @click="deleteUser(user)"
                                    class="text-sm text-red-600 hover:text-red-700"
                                >
                                    删除
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="total > perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    共 {{ total }} 条记录
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="page > 1 && loadUsers(page - 1)"
                        :disabled="page <= 1"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        上一页
                    </button>
                    <span class="text-sm text-gray-700">第 {{ page }} 页</span>
                    <button
                        @click="page * perPage < total && loadUsers(page + 1)"
                        :disabled="page * perPage >= total"
                        class="px-3 py-1.5 rounded border border-gray-300 text-sm hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        下一页
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">{{ isEdit ? '编辑用户' : '新建用户' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">姓名</label>
                            <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                            <input v-model="form.email" type="email" required class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">手机号</label>
                            <input v-model="form.phone" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isEdit ? '新密码（留空不修改）' : '密码' }}</label>
                            <input v-model="form.password" type="password" :required="!isEdit" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div v-if="!isEdit" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">确认密码</label>
                            <input v-model="form.password_confirmation" type="password" :required="!isEdit" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">用户类型</label>
                            <select v-model="form.user_type" @change="form.supplier_id = null; form.distributor_id = null" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="platform">平台端</option>
                                <option value="supplier">供应商端</option>
                                <option value="distributor">分销商端</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
                            <select v-model="form.role" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option v-for="role in roles" :key="role.name" :value="role.name">{{ roleLabel(role.name) }}</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="form.user_type === 'supplier'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">关联供应商</label>
                            <select v-model="form.supplier_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="form.user_type === 'distributor'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">关联分销商</label>
                            <select v-model="form.distributor_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm bg-white">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                        <label for="is_active" class="text-sm text-gray-700">启用该用户</label>
                    </div>
                    <div v-if="submitError" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ submitError }}
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            取消
                        </button>
                        <button type="submit" :disabled="submitting" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition-colors disabled:opacity-50">
                            {{ submitting ? '保存中...' : '保存' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import api from '../../api/axios';

const users = ref([]);
const loading = ref(false);
const search = ref('');
const filterType = ref('');
const filterStatus = ref('');
const page = ref(1);
const perPage = ref(15);
const total = ref(0);
const roles = ref([]);

const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const submitError = ref('');

const defaultForm = () => ({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    user_type: 'platform',
    role: 'platform',
    supplier_id: null,
    distributor_id: null,
    is_active: true,
});

const form = reactive(defaultForm());

const userTypeLabel = (t) => ({ platform: '平台端', supplier: '供应商端', distributor: '分销商端' }[t] || t);
const userTypeBadge = (t) => {
    const map = {
        platform: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700',
        supplier: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
        distributor: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700',
    };
    return map[t] || '';
};
const roleLabel = (r) => ({ platform: '平台管理员', supplier: '供应商', distributor: '批发商', regional_agent: '区域代理' }[r] || r);
const formatDate = (d) => d ? new Date(d).toLocaleString('zh-CN') : '-';

const loadUsers = async (p = 1) => {
    loading.value = true;
    page.value = p;
    try {
        const params = { page: p, per_page: perPage.value };
        if (search.value) params.search = search.value;
        if (filterType.value) params.user_type = filterType.value;
        if (filterStatus.value) params.is_active = filterStatus.value === 'active';
        const { data } = await api.get('/users', { params });
        users.value = data.data;
        total.value = data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const loadRoles = async () => {
    try {
        const { data } = await api.get('/roles');
        roles.value = data.roles;
    } catch (e) {
        console.error(e);
    }
};

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadUsers(1), 300);
});
watch([filterType, filterStatus], () => loadUsers(1));

const openCreateModal = () => {
    Object.assign(form, defaultForm());
    isEdit.value = false;
    editingId.value = null;
    submitError.value = '';
    showModal.value = true;
};

const openEditModal = (user) => {
    Object.assign(form, {
        name: user.name,
        email: user.email,
        phone: user.phone || '',
        password: '',
        password_confirmation: '',
        user_type: user.user_type,
        role: user.roles?.[0] || 'platform',
        supplier_id: user.supplier_id,
        distributor_id: user.distributor_id,
        is_active: user.is_active,
    });
    isEdit.value = true;
    editingId.value = user.id;
    submitError.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = async () => {
    submitting.value = true;
    submitError.value = '';
    try {
        const payload = {
            ...form,
            roles: [form.role],
        };
        if (isEdit.value) {
            await api.put(`/users/${editingId.value}`, payload);
        } else {
            await api.post('/users', payload);
        }
        closeModal();
        loadUsers(page.value);
    } catch (e) {
        submitError.value = e.response?.data?.message || '保存失败';
    } finally {
        submitting.value = false;
    }
};

const toggleStatus = async (user) => {
    try {
        await api.put(`/users/${user.id}/toggle-status`);
        loadUsers(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
};

const deleteUser = async (user) => {
    if (!confirm(`确定删除用户「${user.name}」吗？`)) return;
    try {
        await api.delete(`/users/${user.id}`);
        loadUsers(page.value);
    } catch (e) {
        alert(e.response?.data?.message || '删除失败');
    }
};

onMounted(() => {
    loadUsers();
    loadRoles();
});
</script>
