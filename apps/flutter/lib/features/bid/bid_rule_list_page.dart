import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../shared/api/api_client.dart';

class BidRuleListPage extends ConsumerStatefulWidget {
  const BidRuleListPage({super.key});
  @override ConsumerState<BidRuleListPage> createState() => _BidRuleListPageState();
}

class _BidRuleListPageState extends ConsumerState<BidRuleListPage> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _fetch(); }

  Future<void> _fetch() async {
    setState(() { _loading = true; _error = null; });
    try {
      final resp = await ApiClient.dio.get('/bid-rules', queryParameters: {'per_page': '50'});
      setState(() { _items = resp.data['list'] ?? []; _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  String _actionLabel(String type) {
    return {'adjust_budget': '调整预算', 'toggle_pause': '暂停', 'toggle_enable': '启用'}[type] ?? type;
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Text('加载失败: $_error', style: const TextStyle(color: Colors.red)), const SizedBox(height: 12), ElevatedButton(onPressed: _fetch, child: const Text('重试'))]));
    return ListView.builder(
      itemCount: _items.length,
      itemBuilder: (_, i) {
        final item = _items[i];
        return ListTile(
          title: Text(item['name'] ?? ''),
          subtitle: Text('${item['metric']} ${item['condition']} ${item['threshold']}'),
          trailing: Chip(label: Text(_actionLabel(item['action_type'] ?? '')), avatar: Icon(item['enabled'] == true ? Icons.check_circle : Icons.cancel, size: 16)),
        );
      },
    );
  }
}
