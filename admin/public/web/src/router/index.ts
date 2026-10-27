import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/LoginPage.vue'),
    meta: { title: '登录' },
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/DashboardPage.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'accounts',
        name: 'Accounts',
        component: () => import('@/views/account/AccountList.vue'),
        meta: { title: '账户管理' },
      },
      {
        path: 'accounts/bind',
        name: 'AccountBind',
        component: () => import('@/views/account/AccountBind.vue'),
        meta: { title: '绑定账户' },
      },
      {
        path: 'assets',
        name: 'Assets',
        component: () => import('@/views/asset/AssetGallery.vue'),
        meta: { title: '素材库' },
      },
      {
        path: 'sync',
        name: 'SyncStatus',
        component: () => import('@/views/sync/SyncStatus.vue'),
        meta: { title: '同步状态' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
