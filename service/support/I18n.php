<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Simple i18n helper — returns messages in the requested language.
 * Accepts Accept-Language header or explicit ?lang= query parameter.
 */
namespace erik\support;

class I18n
{
    protected static array $messages = [
        'success'            => ['zh-CN' => '操作成功',         'en' => 'Success'],
        'created'            => ['zh-CN' => '创建成功',         'en' => 'Created'],
        'updated'            => ['zh-CN' => '更新成功',         'en' => 'Updated'],
        'deleted'            => ['zh-CN' => '删除成功',         'en' => 'Deleted'],
        'unauthorized'       => ['zh-CN' => '未授权',           'en' => 'Unauthorized'],
        'token_invalid'      => ['zh-CN' => 'Token 无效或已过期', 'en' => 'Token invalid or expired'],
        'invalid_credentials'=> ['zh-CN' => '用户名或密码错误',   'en' => 'Invalid credentials'],
        'not_found'          => ['zh-CN' => '资源不存在',       'en' => 'Not found'],
        'too_many_requests'  => ['zh-CN' => '请求过于频繁，请稍后重试', 'en' => 'Too many requests, try again later'],
        'sync_triggered'     => ['zh-CN' => '同步已触发',       'en' => 'Sync triggered'],
        'account_disabled'   => ['zh-CN' => '账户已禁用',       'en' => 'Account disabled'],
        'forbidden'          => ['zh-CN' => '禁止访问',         'en' => 'Forbidden'],
    ];

    public static function get(string $key, string $lang = 'zh-CN'): string
    {
        return static::$messages[$key][$lang] ?? static::$messages[$key]['zh-CN'] ?? $key;
    }

    public static function detectLang(): string
    {
        $header = request()->header('Accept-Language', 'zh-CN');
        $lang = request()->get('lang', '');
        if ($lang) return str_starts_with($lang, 'en') ? 'en' : 'zh-CN';
        return str_starts_with($header, 'en') ? 'en' : 'zh-CN';
    }
}
