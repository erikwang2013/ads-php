// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
export class StringResources {
  private static lang: string = 'zh';

  static setLang(l: string) { this.lang = l; }

  private static data: Record<string, Record<string, string>> = {
    appTitle:  { zh: '广告管理系统', en: 'Ads Platform' },
    dashboard: { zh: '仪表盘', en: 'Dashboard' },
    campaigns: { zh: '广告计划', en: 'Campaigns' },
    reports:   { zh: '数据报表', en: 'Reports' },
    accounts:  { zh: '平台账户', en: 'Accounts' },
    alerts:    { zh: '告警管理', en: 'Alerts' },
    login:     { zh: '登录', en: 'Login' },
    logout:    { zh: '退出', en: 'Logout' },
    username:  { zh: '用户名', en: 'Username' },
    password:  { zh: '密码', en: 'Password' },
    loading:   { zh: '加载中...', en: 'Loading...' },
    noData:    { zh: '暂无数据', en: 'No Data' },
    save:      { zh: '保存', en: 'Save' },
    cancel:    { zh: '取消', en: 'Cancel' },
    copyright: { zh: 'Copyright (c) 2026 erik', en: 'Copyright (c) 2026 erik' },
  };

  static get(key: string): string {
    return this.data[key]?.[this.lang] ?? key;
  }
}
