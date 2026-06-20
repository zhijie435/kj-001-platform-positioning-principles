import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('shearerline_token') || null,
        user: JSON.parse(localStorage.getItem('shearerline_user') || 'null'),
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        roles: (state) => state.user?.roles || [],
        permissions: (state) => state.user?.permissions || [],
        userType: (state) => state.user?.user_type || null,
    },

    actions: {
        async login(email, password) {
            const { data } = await api.post('/login', { email, password });
            this.token = data.token;
            this.user = data.user;
            localStorage.setItem('shearerline_token', data.token);
            localStorage.setItem('shearerline_user', JSON.stringify(data.user));
            return data;
        },

        async fetchMe() {
            const { data } = await api.get('/me');
            this.user = data;
            localStorage.setItem('shearerline_user', JSON.stringify(data));
            return data;
        },

        async logout() {
            try {
                await api.post('/logout');
            } finally {
                this.reset();
            }
        },

        reset() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('shearerline_token');
            localStorage.removeItem('shearerline_user');
        },

        hasRole(role) {
            return this.roles.includes(role);
        },

        hasPermission(permission) {
            return this.permissions.includes(permission);
        },

        can(permissions) {
            const parts = permissions.split('|');
            return parts.some((p) => this.hasPermission(p));
        },
    },
});
