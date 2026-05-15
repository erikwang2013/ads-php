import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:dio/dio.dart';
import '../../shared/api/api_client.dart';

class CampaignDetailPage extends ConsumerStatefulWidget {
  final String id;
  const CampaignDetailPage({super.key, required this.id});

  @override
  ConsumerState<CampaignDetailPage> createState() =>
      _CampaignDetailPageState();
}

class _CampaignDetailPageState extends ConsumerState<CampaignDetailPage> {
  Map<String, dynamic>? _campaign;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchDetail();
  }

  Future<void> _fetchDetail() async {
    try {
      final response =
          await ApiClient.dio.get('/campaigns/${widget.id}');
      if (mounted) {
        setState(() {
          _campaign = response.data;
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
                _fetchDetail();
              },
              child: const Text('重试'),
            ),
          ],
        ),
      );
    }

    final name = _campaign?['name'] ?? '未知计划';
    final status = _campaign?['status']?.toString() ?? '-';
    final budget = _campaign?['budget'] ?? 0;
    final spend = _campaign?['spend'] ?? 0;
    final platform = _campaign?['platform'] ?? '-';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(name,
                  style: const TextStyle(
                      fontSize: 24, fontWeight: FontWeight.bold)),
              const SizedBox(width: 12),
              Chip(label: Text(status)),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _buildInfoCard('平台', platform.toString()),
              const SizedBox(width: 12),
              _buildInfoCard('预算', '¥$budget'),
              const SizedBox(width: 12),
              _buildInfoCard('消耗', '¥$spend'),
            ],
          ),
          const SizedBox(height: 24),
          const Text('效果趋势',
              style:
                  TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
          const SizedBox(height: 16),
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
                    spots: const [
                      FlSpot(0, 100),
                      FlSpot(1, 150),
                      FlSpot(2, 130),
                      FlSpot(3, 180),
                      FlSpot(4, 200),
                    ],
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
        ],
      ),
    );
  }

  Widget _buildInfoCard(String label, String value) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: const TextStyle(
                      fontSize: 13, color: Colors.grey)),
              const SizedBox(height: 4),
              Text(value,
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      ),
    );
  }
}
