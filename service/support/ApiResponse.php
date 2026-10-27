<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace app\support;

use erik\support\HashidsService;
use erik\support\I18n;
use Webman\Http\Response;

class ApiResponse
{
    protected static ?HashidsService $hashids = null;

    /**
     * Current request language, lazily detected.
     */
    protected static ?string $lang = null;

    /**
     * Get or detect the current request language.
     */
    public static function lang(): string
    {
        if (static::$lang === null) {
            static::$lang = I18n::detectLang();
        }
        return static::$lang;
    }

    /**
     * Explicitly set the language for the current context.
     */
    public static function setLang(string $lang): void
    {
        static::$lang = $lang;
    }

    /**
     * Translate a message key if it exists in I18n, otherwise return as-is.
     */
    protected static function t(string $message): string
    {
        return I18n::get($message, static::lang()) ?: $message;
    }

    /**
     * Get or create the shared HashidsService instance.
     */
    protected static function getHashids(): HashidsService
    {
        if (static::$hashids === null) {
            static::$hashids = new HashidsService();
        }
        return static::$hashids;
    }

    /**
     * Encode a numeric ID to a hashids string for API responses.
     */
    public static function encodeId($id): string
    {
        return static::getHashids()->encode($id);
    }

    /**
     * Recursively walk an array and encode any key ending with 'id' or '_id'
     * using hashids. Skips null values and non-integer/string IDs.
     */
    protected static function encodeIdsRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::encodeIdsRecursive($value);
            } elseif (
                ($key === 'id' || str_ends_with($key, '_id')) &&
                (is_int($value) || (is_string($value) && ctype_digit($value))) &&
                $value !== null
            ) {
                $data[$key] = static::encodeId($value);
            }
        }
        return $data;
    }

    public static function json(int $code, string $message, mixed $data = null): \Webman\Http\Response
    {
        $body = ['code' => $code, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    public static function success(mixed $data = null, string $message = 'success', bool $encodeIds = false): \Webman\Http\Response
    {
        if ($encodeIds && is_array($data)) {
            $data = static::encodeIdsRecursive($data);
        }
        $msg = static::t($message);
        return static::json(0, $msg, $data);
    }

    public static function error(string $message, int $code = 1, int $httpCode = 200): \Webman\Http\Response
    {
        return new Response($httpCode, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE));
    }

    public static function paginated(array $list, int $total, int $page, int $perPage, ?array $summary = null): \Webman\Http\Response
    {
        $data = [
            'list' => $list,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
        if ($summary !== null) {
            $data['summary'] = $summary;
        }
        return static::success($data);
    }
}
