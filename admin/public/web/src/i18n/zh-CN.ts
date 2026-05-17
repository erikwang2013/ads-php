/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
export default {
  app: { title: '广告管理系统', copyright: 'Copyright (c) 2026 erik' },
  nav: { dashboard: '仪表盘', campaigns: '广告计划', accounts: '账户管理', reports: '数据报表', alerts: '告警管理', alertRules: '告警规则', alertLogs: '告警记录', reportExport: '报表导出', system: '系统管理', userManage: '用户管理', auditLog: '审计日志', apiDocs: 'API 文档' },
  login: { title: '广告管理系统', username: '用户名', password: '密码', login: '登 录', usernameRequired: '请输入用户名', passwordRequired: '请输入密码' },
  dashboard: { title: '仪表盘', todayCost: '今日花费', impressions: '展示量', clicks: '点击量', ctr: '点击率', cvr: '转化率', avgCpa: '平均CPA', platformCost: '平台花费占比', topCampaigns: 'TOP10 广告计划', trend: '花费趋势', exportPdf: '导出PDF', exportExcel: '导出Excel', last7days: '最近7天', last30days: '最近30天', today: '今天', yesterday: '昨天', vsYesterday: '较昨日' },
  campaign: { title: '广告计划', create: '创建计划', edit: '编辑计划', name: '计划名称', platform: '投放平台', account: '平台账户', dailyBudget: '日预算', status: '状态', enabled: '投放中', paused: '已暂停', deleted: '已删除', todayCost: '今日花费', actions: '操作', toggleOn: '启用', toggleOff: '暂停', batchToggle: '批量启停', batchEnabled: '批量启用', batchDisabled: '批量暂停', selected: '已选 {count} 项', search: '搜索计划名称', allPlatforms: '全部平台', allStatus: '全部状态', cancel: '取消', confirm: '确定', createSuccess: '创建成功', updateSuccess: '更新成功', toggleSuccess: '已{action}' },
  account: { title: '平台账户', bind: '绑定新账户', platform: '平台', accountName: '账户名称', platformAccountId: '平台账户ID', status: '状态', lastSync: '最近同步', actions: '操作', sync: '同步', unbind: '解绑', normal: '正常', disabled: '已禁用', notSynced: '未同步', syncTriggered: '同步已触发', unbindConfirm: '确定要解绑该账户吗？', unbindSuccess: '已解绑', bindTitle: '绑定平台账户', selectPlatform: '选择平台', authorizeLogin: '授权登录', complete: '完成绑定', next: '下一步', goAuth: '前往授权', callbackUrl: '回调地址', bindSuccess: '绑定成功', viewAccounts: '查看账户列表', continueBind: '继续绑定' },
  report: { title: '自定义报表', export: '导出', format: '格式', csv: 'CSV', excel: 'Excel', dashboardPdf: '仪表盘 PDF', dimensions: '维度', metrics: '指标', dateRange: '日期范围', platform: '平台', startDate: '开始日期', endDate: '结束日期', exportReport: '导出报表', cost: '花费', impressions: '展示量', clicks: '点击量', conversions: '转化数', roi: 'ROI', cpc: 'CPC', cpm: 'CPM' },
  alert: { rules: '告警规则', logs: '告警记录', createRule: '创建规则', editRule: '编辑规则', ruleName: '规则名称', metric: '监控指标', condition: '条件', threshold: '阈值', scope: '监控范围', channels: '通知渠道', checkInterval: '检查间隔(分钟)', status: '状态', enabled: '已启用', disabled: '已禁用', triggered: '已触发', acknowledged: '已确认', resolved: '已解决', acknowledge: '确认', unreadCount: '未读数量', allStatus: '全部状态', web: '站内通知', email: '邮件', sms: '短信', tenant: '全租户', byPlatform: '按平台', byCampaign: '按计划' },
  system: { userManage: '用户管理', auditLog: '审计日志', username: '用户名', name: '姓名', email: '邮箱', role: '角色', lastLogin: '最后登录', actions: '操作', edit: '编辑', disable: '禁用', createUser: '创建用户', editUser: '编辑用户', password: '密码', passwordOptional: '密码（留空不修改）', operator: '操作人', action: '操作类型', resource: '资源', resourceId: '资源ID', detail: '详情', ip: 'IP地址', time: '操作时间' },
  common: { save: '保存', cancel: '取消', confirm: '确定', delete: '删除', edit: '编辑', search: '搜索', reset: '重置', loading: '加载中...', noData: '暂无数据', total: '共 {total} 条', page: '第 {page} 页', export: '导出', refresh: '刷新', back: '返回', logout: '退出', yes: '是', no: '否', success: '操作成功', error: '操作失败', networkError: '网络错误', confirmDelete: '确定要删除吗？', yuan: '元', fen: '分' },
  confirm: { typeConfirm: '请输入 {word} 以确认', title: '确认操作' },
  captcha: { title: '安全验证', slideHint: '请按住滑块拖动', success: '验证通过', failed: '验证失败', required: '请先完成验证' },
  time: { minute: '分钟', hour: '小时', day: '天' },
  metric: { cost: '花费', impressions: '展示量', clicks: '点击量', conversions: '转化数', ctr: '点击率', cvr: '转化率', roi: 'ROI' },
  condition: { gt: '大于', gte: '大于等于', lt: '小于', lte: '小于等于' },
}
