import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:intl/intl.dart';
import 'package:dio/dio.dart';
import '../../shared/api/api_client.dart';

class ReportPage extends ConsumerStatefulWidget {
  const ReportPage({super.key});

  @override
  ConsumerState<ReportPage> createState() => _ReportPageState();
}

class _ReportPageState extends ConsumerState<ReportPage> {
  DateTimeRange? _dateRange;
  List<Map<String, dynamic>> _reportData = [];
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _dateRange = DateTimeRange(
      start: now.subtract(const Duration(days: 7)),
      end: now,
    );
    _fetchReport();
  }

  Future<void> _pickDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: _dateRange,
    );
    if (picked != null) {
      setState(() => _dateRange = picked);
      _fetchReport();
    }
  }

  Future<void> _fetchReport() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final start = DateFormat('yyyy-MM-dd').format(_dateRange!.start);
      final end = DateFormat('yyyy-MM-dd').format(_dateRange!.end);
      final response = await ApiClient.dio.get('/reports/custom',
          queryParameters: {
            'date_start': start,
            'date_end': end,
            'dimensions[]': ['date'],
            'metrics[]': ['cost', 'impressions', 'clicks', 'conversions'],
            'per_page': 100,
          });
      if (mounted) {
        setState(() {
          // ReportBuilder::buildCustom → data: {list: [...], pagination: {...}}
          final data = response.data?['data'] as Map<String, dynamic>? ?? const {};
          _reportData = List<Map<String, dynamic>>.from(data['list'] ?? const []);
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
    final fmt = DateFormat('yyyy-MM-dd');

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
                    child: Text('数据报表',
                        style: TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                  ),
                  OutlinedButton.icon(
                    onPressed: _pickDateRange,
                    icon: const Icon(Icons.date_range),
                    label: Text(
                      _dateRange != null
                          ? '${fmt.format(_dateRange!.start)} - ${fmt.format(_dateRange!.end)}'
                          : '选择日期范围',
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton(
                    onPressed: () {},
                    icon: const Icon(Icons.download),
                    tooltip: '导出报表',
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Expanded(
                child: _loading
                    ? const Center(child: CircularProgressIndicator())
                    : _error != null
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text('加载失败: $_error'),
                                const SizedBox(height: 16),
                                ElevatedButton(
                                  onPressed: _fetchReport,
                                  child: const Text('重试'),
                                ),
                              ],
                            ),
                          )
                        : _buildReportContent(isDesktop),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildReportContent(bool isDesktop) {
    if (_reportData.isEmpty) {
      return const Center(child: Text('暂无数据'));
    }

    return SingleChildScrollView(
      child: Column(
        children: [
          SizedBox(
            height: 250,
            child: LineChart(
              LineChartData(
                gridData: const FlGridData(show: true),
                titlesData: const FlTitlesData(
                  leftTitles: AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                ),
                borderData: FlBorderData(show: true),
                lineBarsData: [
                  LineChartBarData(
                    spots: _reportData.asMap().entries.map((e) {
                      return FlSpot(
                        e.key.toDouble(),
                        _toDouble(e.value['cost']),
                      );
                    }).toList(),
                    isCurved: true,
                    color: Colors.blue,
                    barWidth: 2,
                    belowBarData: BarAreaData(
                      show: true,
                      color: Colors.blue.withOpacity(0.1),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Card(
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('日期')),
                  DataColumn(label: Text('消耗')),
                  DataColumn(label: Text('展示')),
                  DataColumn(label: Text('点击')),
                  DataColumn(label: Text('转化')),
                ],
                rows: _reportData.map((r) {
                  return DataRow(cells: [
                    DataCell(Text('${r['date'] ?? '-'}')),
                    DataCell(Text('¥${r['cost'] ?? 0}')),
                    DataCell(Text('${r['impressions'] ?? 0}')),
                    DataCell(Text('${r['clicks'] ?? 0}')),
                    DataCell(Text('${r['conversions'] ?? 0}')),
                  ]);
                }).toList(),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// 兼容后端返回的数值类型（int/double/数字字符串，如 SQL SUM 返回 "123.45"）
double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
