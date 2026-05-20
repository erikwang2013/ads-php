import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:window_manager/window_manager.dart';
import 'app.dart';
import 'shared/api/api_client.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await ApiClient.init();

  if (Platform.isMacOS || Platform.isWindows || Platform.isLinux) {
    await windowManager.ensureInitialized();
    await windowManager.setMinimumSize(const Size(680, 480));
    await windowManager.setSize(const Size(1280, 800));
    await windowManager.center();
    windowManager.setTitleBarStyle(TitleBarStyle.hidden);
  }

  runApp(const ProviderScope(child: AdsApp()));
}
