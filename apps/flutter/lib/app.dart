import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'router.dart';
import 'theme.dart';
import 'i18n/app_localizations.dart';

class AdsApp extends ConsumerWidget {
  const AdsApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    return MaterialApp.router(
      title: '广告管理系统',
      theme: AppTheme.lightTheme,
      routerConfig: router,
      localizationsDelegates: const [AppLocalizationsDelegate()],
      supportedLocales: const [Locale('zh', 'CN'), Locale('en', 'US')],
      debugShowCheckedModeBanner: false,
    );
  }
}
