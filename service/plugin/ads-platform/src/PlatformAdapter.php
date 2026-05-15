<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_platform\src;

interface PlatformAdapter
{
    public function code(): string;
    public function name(): string;
    public function capabilities(): array;

    // 授权
    public function buildAuthUrl(string $redirectUri, string $state): string;
    public function exchangeToken(string $code, string $redirectUri): array;
    public function refreshToken(string $refreshToken): array;

    // 获取账户信息
    public function fetchAccountInfo(string $accessToken): array;

    // 数据同步（返回 Generator，yield 统一 Campaign/AdGroup/Creative 对象）
    public function fetchCampaigns(string $accessToken, string $accountId): \Generator;
    public function fetchAdGroups(string $accessToken, string $accountId, string $campaignId): \Generator;
    public function fetchCreatives(string $accessToken, string $accountId, string $adGroupId): \Generator;
    public function fetchReports(string $accessToken, string $accountId, ReportRequest $req): \Generator;

    // 投放操作
    public function createCampaign(string $accessToken, string $accountId, CampaignData $data): string;
    public function updateCampaign(string $accessToken, string $accountId, string $platformId, CampaignData $data): void;
    public function toggleCampaign(string $accessToken, string $accountId, string $platformId, bool $enabled): void;
}
