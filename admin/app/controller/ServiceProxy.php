<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\controller;

class ServiceProxy
{
    protected static string $baseUrl;

    public static function init(): void
    {
        static::$baseUrl = config('app.service_api_url', 'http://127.0.0.1:8788/api');
    }

    public static function get(string $path, array $params = [], ?string $token = null): array
    {
        $url = static::$baseUrl . $path;
        if ($params) $url .= '?' . http_build_query($params);

        $headers = ['Content-Type: application/json', 'X-API-Version: v1'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['code' => 1, 'message' => 'Service unavailable: ' . $error];
        }
        curl_close($ch);
        return json_decode($body, true) ?: [];
    }

    public static function post(string $path, array $data = [], ?string $token = null): array
    {
        $ch = curl_init();

        $headers = ['Content-Type: application/json', 'X-API-Version: v1'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;

        curl_setopt_array($ch, [
            CURLOPT_URL            => static::$baseUrl . $path,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['code' => 1, 'message' => 'Service unavailable: ' . $error];
        }
        curl_close($ch);
        return json_decode($body, true) ?: [];
    }
}

ServiceProxy::init();
