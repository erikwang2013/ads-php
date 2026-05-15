import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'side_nav.dart';
import 'top_bar.dart';

class AppShell extends ConsumerWidget {
  final Widget child;
  const AppShell({super.key, required this.child});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final isDesktop = constraints.maxWidth > 1200;
        final isTablet =
            constraints.maxWidth > 600 && constraints.maxWidth <= 1200;

        if (isDesktop) {
          return Scaffold(
            body: Row(
              children: [
                const SizedBox(width: 250, child: SideNav()),
                const VerticalDivider(width: 1),
                Expanded(
                  child: Column(
                    children: [
                      const TopBar(),
                      Expanded(child: child),
                    ],
                  ),
                ),
              ],
            ),
          );
        }

        if (isTablet) {
          return Scaffold(
            drawer: const Drawer(child: SideNav()),
            body: Column(
              children: [
                const TopBar(),
                Expanded(child: child),
              ],
            ),
          );
        }

        // Mobile: bottom navigation bar
        return Scaffold(
          body: child,
          bottomNavigationBar: NavigationBar(
            destinations: const [
              NavigationDestination(
                icon: Icon(Icons.dashboard),
                label: '仪表盘',
              ),
              NavigationDestination(
                icon: Icon(Icons.campaign),
                label: '广告',
              ),
              NavigationDestination(
                icon: Icon(Icons.bar_chart),
                label: '报表',
              ),
              NavigationDestination(
                icon: Icon(Icons.person),
                label: '账户',
              ),
            ],
          ),
        );
      },
    );
  }
}
