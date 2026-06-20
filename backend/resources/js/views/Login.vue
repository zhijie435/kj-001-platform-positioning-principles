<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Shearerline</h1>
                    <p class="text-gray-500 mt-1">B2B 供应链交易监管平台</p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">邮箱地址</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors outline-none"
                            placeholder="请输入邮箱"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">密码</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors outline-none"
                            placeholder="请输入密码"
                        />
                    </div>

                    <div v-if="error" class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3 px-4 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-medium hover:from-indigo-600 hover:to-purple-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                        {{ loading ? '登录中...' : '登录' }}
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-xs text-gray-500 text-center mb-3">测试账号（密码均为 password123）</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button @click="fillAccount('admin@shearerline.com')" class="px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors">
                            平台管理员
                        </button>
                        <button @click="fillAccount('supplier@shearerline.com')" class="px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors">
                            供应商
                        </button>
                        <button @click="fillAccount('agent@shearerline.com')" class="px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors">
                            区域代理
                        </button>
                        <button @click="fillAccount('distributor@shearerline.com')" class="px-3 py-2 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-700 transition-colors">
                            批发商
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
});

const loading = ref(false);
const error = ref('');

const fillAccount = (email) => {
    form.email = email;
    form.password = 'password123';
    error.value = '';
};

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        await auth.login(form.email, form.password);
        router.push({ name: 'dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || '登录失败，请检查邮箱和密码';
    } finally {
        loading.value = false;
    }
};
</script>
