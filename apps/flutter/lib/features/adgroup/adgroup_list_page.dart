import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../shared/api/api_client.dart';

class AdGroupListPage extends ConsumerStatefulWidget {
  const AdGroupListPage({super.key});
  @override ConsumerState<AdGroupListPage> createState() => _AdGroupListPageState();
}

class _AdGroupListPageState extends ConsumerState<AdGroupListPage> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _fetch(); }

  Future<void> _fetch() async {
    setState(() { _loading = true; _error = null; });
    try {
      final resp = await ApiClient.dio.get('/ad-groups', queryParameters: {'per_page': '50'});
      setState(() { _items = resp.data['list'] ?? []; _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Text('加载失败: $_error', style: const TextStyle(color: Colors.red)), const SizedBox(height: 12), ElevatedButton(onPressed: _fetch, child: const Text('重试'))]));
    return ListView.builder(
      itemCount: _items.length,
      itemBuilder: (_, i) {
        final item = _items[i];
        return ListTile(title: Text(item['name'] ?? ''), subtitle: Text('${item['campaign_name'] ?? ''} | ${item['status'] ?? ''}'), trailing: Text('¥${(item['bid_amount'] ?? 0) / 100}'));
      },
    );
  }
}
