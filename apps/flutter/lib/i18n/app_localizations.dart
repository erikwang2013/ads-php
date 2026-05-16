// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import 'package:flutter/material.dart';

class AppLocalizations {
  final Locale locale;
  AppLocalizations(this.locale);

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const _localizedValues = <String, Map<String, String>>{
    'appTitle':    {'zh': '广告管理系统', 'en': 'Ads Platform'},
    'dashboard':   {'zh': '仪表盘', 'en': 'Dashboard'},
    'campaigns':   {'zh': '广告计划', 'en': 'Campaigns'},
    'reports':     {'zh': '数据报表', 'en': 'Reports'},
    'accounts':    {'zh': '平台账户', 'en': 'Accounts'},
    'alerts':      {'zh': '告警管理', 'en': 'Alerts'},
    'login':       {'zh': '登录', 'en': 'Login'},
    'logout':      {'zh': '退出', 'en': 'Logout'},
    'username':    {'zh': '用户名', 'en': 'Username'},
    'password':    {'zh': '密码', 'en': 'Password'},
    'todayCost':   {'zh': '今日花费', 'en': 'Today Cost'},
    'impressions': {'zh': '展示量', 'en': 'Impressions'},
    'clicks':      {'zh': '点击量', 'en': 'Clicks'},
    'ctr':         {'zh': '点击率', 'en': 'CTR'},
    'loading':     {'zh': '加载中...', 'en': 'Loading...'},
    'noData':      {'zh': '暂无数据', 'en': 'No Data'},
    'save':        {'zh': '保存', 'en': 'Save'},
    'cancel':      {'zh': '取消', 'en': 'Cancel'},
    'copyright':   {'zh': 'Copyright (c) 2026 erik', 'en': 'Copyright (c) 2026 erik'},
  };

  String get(String key) => _localizedValues[key]?[locale.languageCode] ?? key;
}

class AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const AppLocalizationsDelegate();
  @override bool isSupported(Locale locale) => ['zh', 'en'].contains(locale.languageCode);
  @override Future<AppLocalizations> load(Locale locale) async => AppLocalizations(locale);
  @override bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) => false;
}
