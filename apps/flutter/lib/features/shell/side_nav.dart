import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class SideNav extends StatelessWidget {
  const SideNav({super.key});

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).uri.path;

    return Column(
      children: [
        const SizedBox(height: 20),
        const Text(
          '广告管理系统',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const Divider(),
        _NavItem(
          icon: Icons.dashboard,
          label: '仪表盘',
          path: '/dashboard',
          active: location == '/dashboard',
        ),
        _NavItem(
          icon: Icons.campaign,
          label: '广告计划',
          path: '/campaigns',
          active: location.startsWith('/campaigns'),
        ),
        _NavItem(
          icon: Icons.bar_chart,
          label: '数据报表',
          path: '/reports',
          active: location == '/reports',
        ),
        _NavItem(
          icon: Icons.person,
          label: '平台账户',
          path: '/accounts',
          active: location == '/accounts',
        ),
        _NavItem(
          icon: Icons.notifications,
          label: '告警管理',
          path: '/alerts',
          active: location == '/alerts',
        ),
        const Spacer(),
        const Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'Copyright (c) 2026 erik',
            style: TextStyle(fontSize: 11, color: Colors.grey),
          ),
        ),
      ],
    );
  }
}

class _NavItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String path;
  final bool active;

  const _NavItem({
    required this.icon,
    required this.label,
    required this.path,
    required this.active,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(
        icon,
        color: active ? Colors.blue : Colors.grey,
      ),
      title: Text(
        label,
        style: TextStyle(
          color: active ? Colors.blue : Colors.black87,
          fontWeight: active ? FontWeight.w600 : FontWeight.normal,
        ),
      ),
      selected: active,
      onTap: () => context.go(path),
    );
  }
}
