import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import '../../shared/api/api_client.dart';

class ReportViewPage extends ConsumerStatefulWidget {
  const ReportViewPage({super.key});
  @override ConsumerState<ReportViewPage> createState() => _ReportViewPageState();
}

class _ReportViewPageState extends ConsumerState<ReportViewPage> {
  List<dynamic> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _fetch(); }

  Future<void> _fetch() async {
    setState(() { _loading = true; _error = null; });
    try {
      final today = DateTime.now();
      final weekAgo = today.subtract(const Duration(days: 7));
      final resp = await ApiClient.dio.get('/reports/custom', queryParameters: {
        'dimensions': ['date'],
        'metrics': ['cost', 'impressions', 'clicks'],
        'date_start': weekAgo.toIso8601String().split('T')[0],
        'date_end': today.toIso8601String().split('T')[0],
      });
      setState(() { _items = resp.data['list'] ?? []; _loading = false; });
    } catch (e) {
      setState(() { _error = e.toString(); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Text('加载失败: $_error', style: const TextStyle(color: Colors.red)), const SizedBox(height: 12), ElevatedButton(onPressed: _fetch, child: const Text('重试'))]));
    return Column(
      children: [
        SizedBox(
          height: 280,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: LineChart(LineChartData(
              lineBarsData: [
                LineChartBarData(spots: _items.asMap().entries.map((e) => FlSpot(e.key.toDouble(), (e.value['cost'] ?? 0) / 100.0)).toList(), isCurved: true, color: Colors.blue),
              ],
              titlesData: FlTitlesData(bottomTitles: AxisTitles(sideTitles: SideTitles(showTitles: true, getTitlesWidget: (v, _) => Text(_items[v.toInt()]['date']?.toString().substring(5) ?? '', style: const TextStyle(fontSize: 10))))),
            )),
          ),
        ),
        Expanded(child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) => ListTile(title: Text(_items[i]['date'] ?? ''), trailing: Text('¥${(_items[i]['cost'] ?? 0) / 100}'))),
      )],
    );
  }
}
