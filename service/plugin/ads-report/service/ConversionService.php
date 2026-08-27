<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * ConversionService — 转化数据采集（回传 API）核心逻辑。
 *
 * 金额口径：value 单位「分」（cents），与 ads_report_metrics.cost /
 * CampaignData::$dailyBudget 等既有字段一致（Meta 等平台原生即 cents）。
 * 存储列 ads_conversions.value 为 DECIMAL(12,2)，此处按分原样入库。
 *
 * 归因联动：回传成功仅写入 ads_conversions；归因结果由现有
 * AttributionEngine（plugin\ads_report\service\AttributionEngine）定时/手动
 * 重算，本服务不自动触发，保持简单（Phase 10 Task 2 设计）。
 */

namespace plugin\ads_report\service;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use plugin\ads_report\model\Conversion;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class ConversionService
{
    /** 默认币种 */
    public const DEFAULT_CURRENCY = 'CNY';

    /** 默认转化来源渠道（API 回传） */
    public const DEFAULT_CHANNEL = 'api';

    /** 回传时间允许的最大超前量（分钟），容忍轻微时钟偏差，防脏数据 */
    public const MAX_FUTURE_OFFSET_MINUTES = 60;

    /**
     * 校验并规整回传 payload。
     *
     * 纯逻辑方法（不依赖 DB），便于单元测试。失败抛出 InvalidArgumentException。
     *
     * @param array $input 原始请求体（platform/campaign_id/order_id/conversion_time/value/currency/channel）
     * @param CarbonInterface|null $now 当前时间（可注入以便测试；缺省取 now()）
     * @return array 规整后的入库字段
     * @throws InvalidArgumentException
     */
    public static function validateAndNormalize(array $input, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        foreach (['platform', 'campaign_id', 'order_id', 'conversion_time', 'value'] as $field) {
            if (!array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                throw new InvalidArgumentException("$field is required");
            }
        }

        $platform = trim((string) $input['platform']);
        if ($platform === '') {
            throw new InvalidArgumentException('platform is required');
        }

        $campaignId = (int) $input['campaign_id'];
        if ($campaignId <= 0) {
            throw new InvalidArgumentException('campaign_id must be a positive integer');
        }

        $orderId = trim((string) $input['order_id']);
        if ($orderId === '') {
            throw new InvalidArgumentException('order_id is required');
        }
        if (mb_strlen($orderId) > 128) {
            throw new InvalidArgumentException('order_id too long (max 128 chars)');
        }

        $value = $input['value'];
        if (!is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException('value must be a non-negative number (unit: cents)');
        }

        $conversionTime = static::parseConversionTime($input['conversion_time']);
        if ($conversionTime === null) {
            throw new InvalidArgumentException('conversion_time must be a valid datetime in Y-m-d H:i:s format');
        }
        if ($conversionTime->gt($now->copy()->addMinutes(self::MAX_FUTURE_OFFSET_MINUTES))) {
            throw new InvalidArgumentException('conversion_time must not be later than now + 1 hour');
        }

        $currency = strtoupper(trim((string) ($input['currency'] ?? self::DEFAULT_CURRENCY)));
        $channel = trim((string) ($input['channel'] ?? self::DEFAULT_CHANNEL));

        return [
            'platform'        => $platform,
            'campaign_id'     => $campaignId,
            'order_id'        => $orderId,
            'conversion_time' => $conversionTime->format('Y-m-d H:i:s'),
            'value'           => round((float) $value, 2),
            'currency'        => $currency !== '' ? $currency : self::DEFAULT_CURRENCY,
            'channel'         => $channel !== '' ? $channel : self::DEFAULT_CHANNEL,
        ];
    }

    /**
     * 严格解析 'Y-m-d H:i:s'（拒绝溢出日期，如 2026-02-30）。
     */
    protected static function parseConversionTime(mixed $time): ?Carbon
    {
        if (!is_string($time) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $time)) {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $time);
        if (!$date) {
            return null;
        }
        $errors = \DateTime::getLastErrors();
        if ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }
        // 往返校验：拒绝 PHP 对 2026-02-30 之类的静默归一化
        if ($date->format('Y-m-d H:i:s') !== $time) {
            return null;
        }
        return Carbon::instance($date);
    }

    /**
     * 回传转化：校验 → 归属校验（campaign 存在且属于当前租户）→ 幂等防重 → 写入。
     *
     * @throws InvalidArgumentException 校验/归属/重复失败
     */
    public function create(int $tenantId, array $input): array
    {
        $data = static::validateAndNormalize($input);

        // campaign 必须存在且属于当前租户（tenant 匹配）
        $campaign = DB::table('ads_campaigns')
            ->where('id', $data['campaign_id'])
            ->where('tenant_id', $tenantId)
            ->first();
        if (!$campaign) {
            throw new InvalidArgumentException('campaign_id not found or not owned by tenant');
        }

        // 幂等防重：uk_order(tenant_id, platform, order_id) 唯一键的应用层预检，
        // 避免重复回传直接命中 DB 唯一键报 500。
        $exists = DB::table('ads_conversions')
            ->where('tenant_id', $tenantId)
            ->where('platform', $data['platform'])
            ->where('order_id', $data['order_id'])
            ->exists();
        if ($exists) {
            throw new InvalidArgumentException('duplicate conversion for order_id: ' . $data['order_id']);
        }

        // id 显式生成：本项目 Capsule 未挂载事件分发器（start.php 未调用
        // Capsule::setEventDispatcher），SnowflakeTrait 的 creating 钩子在该
        // 运行环境下不会触发；且模型 $guarded=['id'] 会拦截常规 mass-assignment，
        // 故用 forceCreate（绕过 guarded）显式写入 snowflake id，与既有
        // BIGINT snowflake 主键约定一致（id 用 SnowflakeTrait）。
        $conversion = Conversion::forceCreate([
            'id'              => Conversion::snowflakeId(),
            'tenant_id'       => $tenantId,
            'platform'        => $data['platform'],
            'campaign_id'     => $data['campaign_id'],
            'order_id'        => $data['order_id'],
            'conversion_time' => $data['conversion_time'],
            'value'           => $data['value'],
            'currency'        => $data['currency'],
            'channel'         => $data['channel'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 归因联动：现有 AttributionEngine 定时/手动重算，此处不自动触发（见类注释）。
        return $conversion->toArray();
    }

    /**
     * 转化列表查询（分页）。
     *
     * @param array $filters platform|date_start|date_end|campaign_id
     * @return array [items, total, page, perPage]
     */
    public function search(int $tenantId, array $filters, int $page, int $perPage): array
    {
        $query = DB::table('ads_conversions')->where('tenant_id', $tenantId);

        if (!empty($filters['platform'])) {
            $query->where('platform', (string) $filters['platform']);
        }
        if (!empty($filters['campaign_id'])) {
            $query->where('campaign_id', (int) $filters['campaign_id']);
        }
        if (!empty($filters['date_start'])) {
            $query->where('conversion_time', '>=', $this->normalizeDateBoundary($filters['date_start'], 'start'));
        }
        if (!empty($filters['date_end'])) {
            $query->where('conversion_time', '<=', $this->normalizeDateBoundary($filters['date_end'], 'end'));
        }

        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        return [$paginator->items(), $paginator->total(), $paginator->currentPage(), $paginator->perPage()];
    }

    /**
     * 日期边界规整：'Y-m-d' 补全为 'Y-m-d 00:00:00' / 'Y-m-d 23:59:59'，
     * 已是完整 datetime 则原样使用。
     */
    protected function normalizeDateBoundary(string $value, string $kind): string
    {
        $value = trim($value);
        if (strlen($value) === 10 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ($kind === 'start' ? ' 00:00:00' : ' 23:59:59');
        }
        return $value;
    }
}
