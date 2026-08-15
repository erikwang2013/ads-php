import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:intl/intl.dart';
import '../../shared/api/api_client.dart';
import 'package:dio/dio.dart';

class DashboardPage extends ConsumerStatefulWidget {
  const DashboardPage({super.key});

  @override
  ConsumerState<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends ConsumerState<DashboardPage> {
  Map<String, dynamic>? _dashboardData;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchDashboard();
  }

  Future<void> _fetchDashboard() async {
    try {
      final now = DateTime.now();
      final today = DateFormat('yyyy-MM-dd').format(now);
      final weekAgo =
          DateFormat('yyyy-MM-dd').format(now.subtract(const Duration(days: 6)));

      // 卡片数据：今日汇总（今日消耗/点击率/转化数）
      final todayResponse = await ApiClient.dio.get(
        '/reports/summary',
        queryParameters: {'date_start': today, 'date_end': today},
      );
      // 图表数据：近 7 天趋势与平台分布
      final trendResponse = await ApiClient.dio.get(
        '/reports/summary',
        queryParameters: {'date_start': weekAgo, 'date_end': today},
      );

      if (mounted) {
        setState(() {
          _dashboardData = {
            'overview':
                todayResponse.data?['data']?['overview'] ?? <String, dynamic>{},
            'by_platform':
                trendResponse.data?['data']?['by_platform'] ?? <dynamic>[],
            'daily': trendResponse.data?['data']?['daily'] ?? <dynamic>[],
          };
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
            const Icon(Icons.error_outline, size: 48, color: Colors.red),
            const SizedBox(height: 16),
            Text('加载失败: $_error'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _fetchDashboard();
              },
              child: const Text('重试'),
            ),
          ],
        ),
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final isDesktop = constraints.maxWidth > 1200;
        final isTablet =
            constraints.maxWidth > 600 && constraints.maxWidth <= 1200;

        final crossAxisCount = isDesktop ? 6 : (isTablet ? 3 : 1);

        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                '仪表盘',
                style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              _buildMetricCards(crossAxisCount),
              const SizedBox(height: 24),
              _buildCharts(constraints.maxWidth),
            ],
          ),
        );
      },
    );
  }

  Widget _buildMetricCards(int crossAxisCount) {
    // /reports/summary 返回 {overview, by_platform, daily}，卡片取 overview
    final overview =
        _dashboardData?['overview'] as Map<String, dynamic>? ?? const {};
    final cards = [
      _MetricCard(
        title: '活跃广告',
        // summary 无 campaign 维度，保留默认值
        value: '${_dashboardData?['active_campaigns'] ?? 0}',
        icon: Icons.campaign,
        color: Colors.blue,
      ),
      _MetricCard(
        title: '今日消耗',
        value: '¥${(overview['total_cost'] ?? 0).toString()}',
        icon: Icons.monetization_on,
        color: Colors.green,
      ),
      _MetricCard(
        title: '点击率',
        value: '${(overview['avg_ctr'] ?? '0')}%',
        icon: Icons.touch_app,
        color: Colors.orange,
      ),
      _MetricCard(
        title: '转化数',
        value: '${overview['total_conversions'] ?? 0}',
        icon: Icons.check_circle,
        color: Colors.purple,
      ),
      _MetricCard(
        title: '告警',
        // summary 无告警数，保留默认值
        value: '${_dashboardData?['alerts'] ?? 0}',
        icon: Icons.warning,
        color: Colors.red,
      ),
      _MetricCard(
        title: 'ROI',
        // summary 无 ROI，保留默认值
        value: '${_dashboardData?['roi'] ?? '0'}x',
        icon: Icons.trending_up,
        color: Colors.teal,
      ),
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        childAspectRatio: 1.5,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: cards.length,
      itemBuilder: (_, i) => cards[i],
    );
  }

  Widget _buildCharts(double width) {
    final isDesktop = width > 1200;

    return isDesktop
        ? Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: _buildLineChart()),
              const SizedBox(width: 16),
              Expanded(child: _buildPieChart()),
            ],
          )
        : Column(
            children: [
              _buildLineChart(),
              const SizedBox(height: 16),
              _buildPieChart(),
            ],
          );
  }

  Widget _buildLineChart() {
    // daily 按 (date, platform) 聚合，折线图按日期汇总消耗
    final daily = _dashboardData?['daily'] as List<dynamic>? ?? const [];
    final costByDate = <String, double>{};
    for (final item in daily) {
      final m = Map<String, dynamic>.from(item as Map);
      final date = m['date']?.toString() ?? '';
      if (date.isEmpty) continue;
      costByDate[date] = (costByDate[date] ?? 0) + _toDouble(m['cost']);
    }
    final dates = costByDate.keys.toList();
    final spots = <FlSpot>[
      for (var i = 0; i < dates.length; i++) FlSpot(i.toDouble(), costByDate[dates[i]]!),
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('近7天消耗趋势',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 16),
            SizedBox(
              height: 250,
              child: spots.isEmpty
                  ? const Center(child: Text('暂无数据'))
                  : LineChart(
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
                            spots: spots,
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
      ),
    );
  }

  Widget _buildPieChart() {
    // by_platform 按平台聚合消耗，按 cost 降序，最多展示 4 个平台，其余合并为“其他”
    final byPlatform = _dashboardData?['by_platform'] as List<dynamic>? ?? const [];
    const platformNames = {'juliang': '巨量', 'tencent': '腾讯', 'kuaishou': '快手'};
    const palette = [Colors.blue, Colors.green, Colors.orange, Colors.purple];
    const maxSlices = 4;

    final platformCosts = <(String, double)>[];
    for (final item in byPlatform) {
      final m = Map<String, dynamic>.from(item as Map);
      final platform = m['platform']?.toString() ?? 'unknown';
      platformCosts.add((platform, _toDouble(m['cost'])));
    }
    platformCosts.sort((a, b) => b.$2.compareTo(a.$2));

    final sections = <PieChartSectionData>[];
    var restCost = 0.0;
    for (var i = 0; i < platformCosts.length; i++) {
      final p = platformCosts[i];
      if (i < maxSlices) {
        sections.add(PieChartSectionData(
          value: p.$2,
          title: platformNames[p.$1] ?? p.$1,
          color: palette[i % palette.length],
          radius: 80,
          titleStyle: const TextStyle(
            fontSize: 12,
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ));
      } else {
        restCost += p.$2;
      }
    }
    if (restCost > 0) {
      sections.add(PieChartSectionData(
        value: restCost,
        title: '其他',
        color: Colors.grey,
        radius: 80,
        titleStyle: const TextStyle(
          fontSize: 12,
          color: Colors.white,
          fontWeight: FontWeight.bold,
        ),
      ));
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('平台消耗分布',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 16),
            SizedBox(
              height: 250,
              child: sections.isEmpty
                  ? const Center(child: Text('暂无数据'))
                  : PieChart(PieChartData(sections: sections)),
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _MetricCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 13,
                    color: Colors.grey,
                  ),
                ),
              ],
            ),
            const Spacer(),
            Text(
              value,
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// 兼容后端返回的数值类型（int/double/数字字符串，如 SQL SUM 返回 "123.45"）
double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}
