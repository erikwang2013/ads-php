<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * AttributionEngine — multi-touch attribution across platforms.
 *
 * Models: first_touch, last_touch, linear, time_decay, position_based.
 * Lookback window: 30 days.
 */

namespace plugin\ads_report\service;

use Illuminate\Database\Capsule\Manager as DB;

class AttributionEngine
{
    protected int $lookbackDays = 30;
    protected float $halfLife = 7.0;

    public const MODELS = [
        'first_touch'    => '首次触点 100%',
        'last_touch'     => '末次触点 100%',
        'linear'         => '所有触点均分',
        'time_decay'     => '时间衰减 (7天半衰期)',
        'position_based' => '首40% + 末40% + 中间20%',
    ];

    public function compute(int $tenantId, string $dateStart, string $dateEnd, string $model): array
    {
        $conversions = DB::table('ads_conversions')
            ->where('tenant_id', $tenantId)
            ->whereBetween('conversion_time', [$dateStart . ' 00:00:00', $dateEnd . ' 23:59:59'])
            ->get();

        $results = [];

        foreach ($conversions as $conv) {
            $lookbackStart = date('Y-m-d', strtotime($conv->conversion_time) - $this->lookbackDays * 86400);

            $touchpoints = DB::table('ads_report_metrics')
                ->where('campaign_id', '>', 0)
                ->where('date', '>=', $lookbackStart)
                ->where('date', '<=', date('Y-m-d', strtotime($conv->conversion_time)))
                ->where('clicks', '>', 0)
                ->select('campaign_id', 'platform', 'date', 'clicks')
                ->orderBy('date')
                ->get()
                ->groupBy('campaign_id')
                ->map(function ($rows) {
                    return [
                        'campaign_id' => $rows[0]->campaign_id,
                        'platform'    => $rows[0]->platform,
                        'date'        => $rows->min('date'),
                        'clicks'      => $rows->sum('clicks'),
                    ];
                })
                ->values()
                ->toArray();

            if (empty($touchpoints)) continue;

            $credits = $this->distribute($touchpoints, $model, strtotime($conv->conversion_time));

            DB::table('ads_attribution_results')
                ->where('conversion_id', $conv->id)
                ->where('model', $model)
                ->delete();

            foreach ($credits as $tp) {
                DB::table('ads_attribution_results')->insert([
                    'id'            => snowflake_id(),
                    'tenant_id'     => $tenantId,
                    'conversion_id' => $conv->id,
                    'model'         => $model,
                    'campaign_id'   => $tp['campaign_id'],
                    'credit'        => round($tp['credit'] * (float) $conv->value, 2),
                    'created_at'    => now(),
                ]);
            }

            $results[] = [
                'conversion_id' => $conv->id,
                'order_id'      => $conv->order_id,
                'value'         => (float) $conv->value,
                'platform'      => $conv->platform,
                'touchpoints'   => $touchpoints,
                'credits'       => $credits,
            ];
        }

        return $this->aggregate($results);
    }

    protected function distribute(array $touchpoints, string $model, int $conversionTime): array
    {
        $n = count($touchpoints);
        if ($n === 0) return [];
        if ($n === 1) return [['campaign_id' => $touchpoints[0]['campaign_id'], 'credit' => 1.0]];

        $weights = match ($model) {
            'first_touch' => $this->firstTouch($n),
            'last_touch'  => $this->lastTouch($n),
            'linear'      => $this->linear($n),
            'time_decay'  => $this->timeDecay($touchpoints, $conversionTime),
            'position_based' => $this->positionBased($n),
            default       => $this->lastTouch($n),
        };

        $result = [];
        foreach ($touchpoints as $i => $tp) {
            $result[] = ['campaign_id' => $tp['campaign_id'], 'credit' => $weights[$i]];
        }
        return $result;
    }

    protected function firstTouch(int $n): array { $w = array_fill(0, $n, 0.0); $w[0] = 1.0; return $w; }
    protected function lastTouch(int $n): array { $w = array_fill(0, $n, 0.0); $w[$n - 1] = 1.0; return $w; }
    protected function linear(int $n): array { $v = 1.0 / $n; return array_fill(0, $n, $v); }

    protected function timeDecay(array $touchpoints, int $conversionTime): array
    {
        $lambda = log(2) / ($this->halfLife * 86400);
        $raw = [];
        foreach ($touchpoints as $tp) {
            $delta = $conversionTime - strtotime($tp['date']);
            $raw[] = exp(-$lambda * $delta);
        }
        $sum = array_sum($raw);
        if ($sum <= 0) return $this->linear(count($touchpoints));
        return array_map(fn($v) => $v / $sum, $raw);
    }

    protected function positionBased(int $n): array
    {
        if ($n <= 2) return $this->linear($n);
        $w = array_fill(0, $n, 0.0);
        $w[0] = 0.4;
        $w[$n - 1] = 0.4;
        $mid = $n - 2;
        if ($mid > 0) {
            $each = 0.2 / $mid;
            for ($i = 1; $i < $n - 1; $i++) $w[$i] = $each;
        }
        return $w;
    }

    protected function aggregate(array $results): array
    {
        $byCampaign = [];
        $byPlatform = [];
        $totalValue = 0;

        foreach ($results as $r) {
            $totalValue += $r['value'];
            foreach ($r['credits'] as $c) {
                $cid = $c['campaign_id'];
                if (!isset($byCampaign[$cid])) $byCampaign[$cid] = ['campaign_id' => $cid, 'credit' => 0];
                $byCampaign[$cid]['credit'] += $c['credit'];
            }
        }

        return [
            'total_conversions' => count($results),
            'total_value'       => round($totalValue, 2),
            'by_campaign'       => array_values($byCampaign),
        ];
    }
}
