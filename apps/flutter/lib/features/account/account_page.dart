import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../shared/api/api_client.dart';

class AccountPage extends ConsumerStatefulWidget {
  const AccountPage({super.key});

  @override
  ConsumerState<AccountPage> createState() => _AccountPageState();
}

class _AccountPageState extends ConsumerState<AccountPage> {
  List<Map<String, dynamic>> _accounts = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchAccounts();
  }

  Future<void> _fetchAccounts() async {
    try {
      final response = await ApiClient.dio.get('/accounts');
      if (mounted) {
        setState(() {
          _accounts =
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
                _fetchAccounts();
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
                    child: Text('平台账户',
                        style: TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                  ),
                  ElevatedButton.icon(
                    onPressed: () => _showAddAccountDialog(context),
                    icon: const Icon(Icons.add),
                    label: const Text('添加账户'),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Expanded(
                child: isDesktop ? _buildDataTable() : _buildCardList(),
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
            DataColumn(label: Text('平台')),
            DataColumn(label: Text('账户名称')),
            DataColumn(label: Text('账户ID')),
            DataColumn(label: Text('状态')),
            DataColumn(label: Text('余额')),
            DataColumn(label: Text('操作')),
          ],
          rows: _accounts.map((a) {
            return DataRow(cells: [
              DataCell(Text('${a['id'] ?? '-'}')),
              DataCell(Text('${a['platform'] ?? '-'}')),
              DataCell(Text('${a['name'] ?? '-'}')),
              DataCell(Text('${a['account_id'] ?? '-'}')),
              DataCell(_buildStatusChip(a['status'])),
              DataCell(Text('¥${a['balance'] ?? 0}')),
              DataCell(
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.edit, size: 18),
                      onPressed: () {},
                    ),
                    IconButton(
                      icon: const Icon(Icons.delete,
                          size: 18, color: Colors.red),
                      onPressed: () {},
                    ),
                  ],
                ),
              ),
            ]);
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildCardList() {
    return ListView.builder(
      itemCount: _accounts.length,
      itemBuilder: (context, index) {
        final a = _accounts[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: CircleAvatar(
              child: Text(
                (a['platform']?.toString() ?? '?')[0].toUpperCase(),
              ),
            ),
            title: Text('${a['name'] ?? '-'}'),
            subtitle:
                Text('账户ID: ${a['account_id'] ?? '-'} | 余额: ¥${a['balance'] ?? 0}'),
            trailing: _buildStatusChip(a['status']),
          ),
        );
      },
    );
  }

  Widget _buildStatusChip(dynamic status) {
    final s = status?.toString() ?? '';
    final color = s == 'active'
        ? Colors.green
        : s == 'disabled'
            ? Colors.red
            : Colors.grey;
    return Chip(
      label: Text(s, style: const TextStyle(fontSize: 12)),
      backgroundColor: color.withOpacity(0.1),
      side: BorderSide(color: color),
    );
  }

  void _showAddAccountDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('添加平台账户'),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              decoration: InputDecoration(
                labelText: '平台',
                hintText: '如: 巨量引擎、腾讯广告',
              ),
            ),
            SizedBox(height: 12),
            TextField(
              decoration: InputDecoration(labelText: '账户名称'),
            ),
            SizedBox(height: 12),
            TextField(
              decoration: InputDecoration(labelText: '账户ID'),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('取消'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('添加'),
          ),
        ],
      ),
    );
  }
}
