import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
import '../../shared/api/api_client.dart';

class CampaignListPage extends ConsumerStatefulWidget {
  const CampaignListPage({super.key});

  @override
  ConsumerState<CampaignListPage> createState() => _CampaignListPageState();
}

class _CampaignListPageState extends ConsumerState<CampaignListPage> {
  List<Map<String, dynamic>> _campaigns = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchCampaigns();
  }

  Future<void> _fetchCampaigns() async {
    try {
      final response = await ApiClient.dio.get('/campaigns');
      if (mounted) {
        setState(() {
          _campaigns = List<Map<String, dynamic>>.from(response.data['data'] ?? []);
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
            ElevatedButton(onPressed: () {
              setState(() { _loading = true; _error = null; });
              _fetchCampaigns();
            }, child: const Text('重试')),
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
                    child: Text('广告计划',
                        style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                  ),
                  ElevatedButton.icon(
                    onPressed: () {},
                    icon: const Icon(Icons.add),
                    label: const Text('新建计划'),
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
            DataColumn(label: Text('计划名称')),
            DataColumn(label: Text('平台')),
            DataColumn(label: Text('状态')),
            DataColumn(label: Text('预算')),
            DataColumn(label: Text('消耗')),
            DataColumn(label: Text('操作')),
          ],
          rows: _campaigns.map((c) {
            return DataRow(
              onSelectChanged: (_) => context.go('/campaigns/${c['id']}'),
              cells: [
                DataCell(Text('${c['id'] ?? '-'}')),
                DataCell(Text('${c['name'] ?? '-'}')),
                DataCell(Text('${c['platform'] ?? '-'}')),
                DataCell(_buildStatusChip(c['status'])),
                DataCell(Text('¥${c['budget'] ?? 0}')),
                DataCell(Text('¥${c['spend'] ?? 0}')),
                DataCell(
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(icon: const Icon(Icons.edit, size: 18), onPressed: () {}),
                      IconButton(icon: const Icon(Icons.delete, size: 18, color: Colors.red), onPressed: () {}),
                    ],
                  ),
                ),
              ],
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildCardList() {
    return ListView.builder(
      itemCount: _campaigns.length,
      itemBuilder: (context, index) {
        final c = _campaigns[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            title: Text('${c['name'] ?? '-'}'),
            subtitle: Text('平台: ${c['platform'] ?? '-'} | 消耗: ¥${c['spend'] ?? 0}'),
            trailing: _buildStatusChip(c['status']),
            onTap: () => context.go('/campaigns/${c['id']}'),
          ),
        );
      },
    );
  }

  Widget _buildStatusChip(dynamic status) {
    final s = status?.toString() ?? '';
    final color = s == 'active'
        ? Colors.green
        : s == 'paused'
            ? Colors.orange
            : Colors.grey;
    return Chip(
      label: Text(s, style: const TextStyle(fontSize: 12)),
      backgroundColor: color.withOpacity(0.1),
      side: BorderSide(color: color),
    );
  }
}
