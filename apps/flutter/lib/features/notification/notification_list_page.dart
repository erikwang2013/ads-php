import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../shared/api/api_client.dart';

class NotificationListPage extends ConsumerStatefulWidget {
  const NotificationListPage({super.key});
  @override ConsumerState<NotificationListPage> createState() => _NotificationListPageState();
}

class _NotificationListPageState extends ConsumerState<NotificationListPage> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _fetch(); }

  Future<void> _fetch() async {
    setState(() { _loading = true; _error = null; });
    try {
      final resp = await ApiClient.dio.get('/notifications', queryParameters: {'per_page': '50'});
      setState(() { _items = resp.data['list'] ?? []; _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  Future<void> _markAllRead() async {
    await ApiClient.dio.post('/notifications/read-all');
    _fetch();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('通知中心'), actions: [TextButton(onPressed: _markAllRead, child: const Text('全部已读'))]),
      body: _loading ? const Center(child: CircularProgressIndicator()) :
            _error != null ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Text('加载失败: $_error', style: const TextStyle(color: Colors.red)), const SizedBox(height: 12), ElevatedButton(onPressed: _fetch, child: const Text('重试'))])) :
            ListView.builder(
              itemCount: _items.length,
              itemBuilder: (_, i) {
                final item = _items[i];
                return ListTile(title: Text(item['title'] ?? ''), subtitle: Text(item['content'] ?? ''), trailing: item['is_read'] == 1 ? null : const Icon(Icons.circle, size: 8, color: Colors.orange));
              },
            ),
    );
  }
}
