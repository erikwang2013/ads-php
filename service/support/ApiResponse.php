<?php
namespace app\support;

class ApiResponse
{
    public static function json(int $code, string $message, mixed $data = null): \Webman\Http\Response
    {
        $body = ['code' => $code, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        return new \Webman\Http\Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    public static function success(mixed $data = null, string $message = 'success'): \Webman\Http\Response
    {
        return static::json(0, $message, $data);
    }

    public static function error(string $message, int $code = 1, int $httpCode = 200): \Webman\Http\Response
    {
        return new \Webman\Http\Response($httpCode, ['Content-Type' => 'application/json'], json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE));
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
