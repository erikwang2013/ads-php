import 'package:flutter/material.dart';

class MenuItem {
  final String label;
  final String? path;
  final IconData icon;
  final List<MenuItem>? children;

  const MenuItem({
    required this.label,
    this.path,
    required this.icon,
    this.children,
  });

  bool get hasChildren => children != null && children!.isNotEmpty;
}

const List<MenuItem> menuConfig = [
  MenuItem(label: '仪表盘', path: '/dashboard', icon: Icons.dashboard),
  MenuItem(label: '广告管理', icon: Icons.campaign, children: [
    MenuItem(label: '广告计划', path: '/campaigns/list', icon: Icons.list_alt),
    MenuItem(label: '广告组', path: '/adgroups', icon: Icons.view_list),
    MenuItem(label: '广告创意', path: '/creatives', icon: Icons.image),
  ]),
  MenuItem(label: '数据报表', icon: Icons.bar_chart, children: [
    MenuItem(label: '报表分析', path: '/reports/view', icon: Icons.analytics),
    MenuItem(label: '数据报表', path: '/reports', icon: Icons.bar_chart),
  ]),
  MenuItem(label: '平台账户', path: '/accounts', icon: Icons.person),
  MenuItem(label: '告警管理', path: '/alerts', icon: Icons.notifications),
  MenuItem(label: '自动出价', path: '/bid-rules', icon: Icons.auto_graph),
  MenuItem(label: '通知中心', path: '/notifications', icon: Icons.markunread),
];

/// Build breadcrumb trail for a given route path.
/// Returns the chain of MenuItems from root to the matched item.
List<MenuItem> buildBreadcrumb(String path) {
  for (final item in menuConfig) {
    if (item.path == path) return [item];
    if (item.hasChildren) {
      for (final child in item.children!) {
        if (child.path == path) return [item, child];
      }
    }
  }
  // Fallback: prefix-match for parameterized routes like /campaigns/:id
  for (final item in menuConfig) {
    if (item.hasChildren) {
      for (final child in item.children!) {
        if (path.startsWith('${child.path}/')) return [item, child];
      }
    }
  }
  return [];
}

/// Find the label for a given route path. Returns empty string if not found.
String routeLabel(String path) {
  for (final item in menuConfig) {
    if (item.path == path) return item.label;
    if (item.hasChildren) {
      for (final child in item.children!) {
        if (child.path == path) return child.label;
      }
    }
  }
  return '';
}
