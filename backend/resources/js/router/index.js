import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../views/Dashboard.vue'),
            },
            {
                path: 'suppliers',
                name: 'suppliers.index',
                component: () => import('../views/suppliers/Index.vue'),
                meta: { permission: 'supplier.view' },
            },
            {
                path: 'distributors',
                name: 'distributors.index',
                component: () => import('../views/distributors/Index.vue'),
                meta: { permission: 'distributor.view' },
            },
            {
                path: 'products',
                name: 'products.index',
                component: () => import('../views/products/Index.vue'),
                meta: { permission: 'product.view' },
            },
            {
                path: 'orders',
                name: 'orders.index',
                component: () => import('../views/orders/Index.vue'),
                meta: { permission: 'order.view' },
            },
            {
                path: 'categories',
                name: 'categories.index',
                component: () => import('../views/categories/Index.vue'),
            },
            {
                path: 'inventory',
                name: 'inventory.index',
                component: () => import('../views/inventory/Index.vue'),
                meta: { permission: 'inventory.view' },
            },
            {
                path: 'payments',
                name: 'payments.index',
                component: () => import('../views/payments/Index.vue'),
                meta: { permission: 'payment.view' },
            },
            {
                path: 'users',
                name: 'users.index',
                component: () => import('../views/users/Index.vue'),
                meta: { permission: 'user.manage' },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const auth = useAuthStore();

    if (to.meta.guest && auth.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
