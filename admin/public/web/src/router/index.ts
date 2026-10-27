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
        path: 'campaigns',
        name: 'Campaigns',
        component: () => import('@/views/campaign/CampaignList.vue'),
        meta: { title: '广告计划' },
      },
      {
        path: 'adgroups',
        name: 'AdGroups',
        component: () => import('@/views/adgroup/AdGroupList.vue'),
        meta: { title: '广告组' },
      },
      {
        path: 'creatives',
        name: 'Creatives',
        component: () => import('@/views/creative/CreativeList.vue'),
        meta: { title: '广告创意' },
      },
      {
        path: 'alerts',
        name: 'Alerts',
        component: () => import('@/views/alert/AlertRuleList.vue'),
        meta: { title: '告警规则' },
      },
      {
        path: 'alerts/logs',
        name: 'AlertLogs',
        component: () => import('@/views/alert/AlertLogList.vue'),
        meta: { title: '告警记录' },
      },
      {
        path: 'reports/export',
        name: 'ReportExport',
        component: () => import('@/views/report/ReportExport.vue'),
        meta: { title: '报表导出' },
      },
      {
        path: 'reports/calendar',
        name: 'CampaignCalendar',
        component: () => import('@/views/report/CampaignCalendar.vue'),
        meta: { title: '投放日历' },
      },
      {
        path: 'reports/attribution',
        name: 'AttributionReport',
        component: () => import('@/views/report/AttributionReport.vue'),
        meta: { title: '归因分析' },
      },
      {
        path: 'assets',
        name: 'Assets',
        component: () => import('@/views/asset/AssetGallery.vue'),
        meta: { title: '素材库' },
      },
      {
        path: 'reports/view',
        name: 'ReportView',
        component: () => import('@/views/report/ReportView.vue'),
        meta: { title: '报表分析' },
      },
      {
        path: 'notifications',
        name: 'Notifications',
        component: () => import('@/views/notification/NotificationList.vue'),
        meta: { title: '通知中心' },
      },
      {
        path: 'sync',
        name: 'SyncStatus',
        component: () => import('@/views/sync/SyncStatus.vue'),
        meta: { title: '同步状态' },
      },
      {
        path: 'system/users',
        name: 'UserManage',
        component: () => import('@/views/system/UserManage.vue'),
        meta: { title: '用户管理' },
      },
      {
        path: 'system/audit',
        name: 'AuditLog',
        component: () => import('@/views/system/AuditLog.vue'),
        meta: { title: '审计日志' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
