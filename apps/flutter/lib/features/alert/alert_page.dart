import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../shared/api/api_client.dart';
import 'package:intl/intl.dart';

class AlertPage extends ConsumerStatefulWidget {
  const AlertPage({super.key});

  @override
  ConsumerState<AlertPage> createState() => _AlertPageState();
}

class _AlertPageState extends ConsumerState<AlertPage> {
  List<Map<String, dynamic>> _alerts = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchAlerts();
  }

  Future<void> _fetchAlerts() async {
    try {
      final response = await ApiClient.dio.get('/alerts');
      if (mounted) {
        setState(() {
          _alerts =
              List<Map<String, dynamic>>.from(response.data['data'] ?? []);
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('加载失败: $_error'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetchAlerts();
              },
              child: const Text('重试'),
            ),
          ],
        ),
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final isDesktop = constraints.maxWidth > 900;

        return Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  const Expanded(
                    child: Text('告警管理',
                        style: TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                  ),
                  Text('共 ${_alerts.length} 条告警',
                      style: const TextStyle(color: Colors.grey)),
                  const SizedBox(width: 8),
                  IconButton(
                    icon: const Icon(Icons.refresh),
                    onPressed: _fetchAlerts,
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Expanded(
                child: isDesktop ? _buildDataTable() : _buildAlertList(),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDataTable() {
    return Card(
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: DataTable(
          columns: const [
            DataColumn(label: Text('ID')),
            DataColumn(label: Text('级别')),
            DataColumn(label: Text('标题')),
            DataColumn(label: Text('内容')),
            DataColumn(label: Text('时间')),
            DataColumn(label: Text('状态')),
            DataColumn(label: Text('操作')),
          ],
          rows: _alerts.map((a) {
            return DataRow(cells: [
              DataCell(Text('${a['id'] ?? '-'}')),
              DataCell(_buildLevelBadge(a['level'])),
              DataCell(Text('${a['title'] ?? '-'}')),
              DataCell(Text('${a['content'] ?? '-'}', maxLines: 1, overflow: TextOverflow.ellipsis)),
              DataCell(Text(_formatTime(a['created_at']))),
              DataCell(_buildStatusChip(a['status'])),
              DataCell(
                TextButton(
                  onPressed: () {},
                  child: const Text('处理'),
                ),
              ),
            ]);
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildAlertList() {
    return ListView.builder(
      itemCount: _alerts.length,
      itemBuilder: (context, index) {
        final a = _alerts[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: _buildLevelIcon(a['level']),
            title: Text('${a['title'] ?? '-'}'),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${a['content'] ?? '-'}', maxLines: 2, overflow: TextOverflow.ellipsis),
                const SizedBox(height: 4),
                Text(_formatTime(a['created_at']),
                    style: const TextStyle(fontSize: 11, color: Colors.grey)),
              ],
            ),
            trailing: _buildStatusChip(a['status']),
            onTap: () {},
          ),
        );
      },
    );
  }

  Widget _buildLevelBadge(dynamic level) {
    final s = level?.toString() ?? '';
    final color = s == 'critical'
        ? Colors.red
        : s == 'warning'
            ? Colors.orange
            : Colors.blue;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color),
      ),
      child: Text(s, style: TextStyle(fontSize: 11, color: color)),
    );
  }

  Widget _buildLevelIcon(dynamic level) {
    final s = level?.toString() ?? '';
    final icon = s == 'critical'
        ? Icons.error
        : s == 'warning'
            ? Icons.warning_amber
            : Icons.info_outline;
    final color = s == 'critical'
        ? Colors.red
        : s == 'warning'
            ? Colors.orange
            : Colors.blue;
    return Icon(icon, color: color);
  }

  Widget _buildStatusChip(dynamic status) {
    final s = status?.toString() ?? '';
    final color = s == 'unread'
        ? Colors.red
        : s == 'read'
            ? Colors.blue
            : Colors.grey;
    return Chip(
      label: Text(s, style: const TextStyle(fontSize: 12)),
      backgroundColor: color.withOpacity(0.1),
      side: BorderSide(color: color),
    );
  }

  String _formatTime(dynamic time) {
    if (time == null) return '-';
    try {
      final dt = DateTime.parse(time.toString());
      return DateFormat('yyyy-MM-dd HH:mm').format(dt);
    } catch (_) {
      return time.toString();
    }
  }
}
