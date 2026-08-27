# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

サービスに新しい RESTful API エンドポイントを追加します。

## Pattern

すべてのエンドポイントは RESTful 規約に従い、`app\support\ApiResponse` を使用し、JWT で保護されます（パブリック以外）。

## Controller Template
```php
<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller;

use Webman\Http\Request;
use app\support\ApiResponse;
use Throwable;

class ExampleController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $request->tenantId ?? 1;
        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = DB::table('ads_example')->where('tenant_id', $tenantId)->paginate($perPage);

        return ApiResponse::paginated(
            $paginator->items(), $paginator->total(),
            $paginator->currentPage(), $paginator->perPage()
        );
    }

    public function show(int $id): \Webman\Http\Response
    {
        $item = DB::table('ads_example')->find($id);
        if (!$item) return ApiResponse::error('Not found');
        return ApiResponse::success($item);
    }

    public function store(Request $request): \Webman\Http\Response
    {
        $id = DB::table('ads_example')->insertGetId([
            'name' => $request->post('name'),
            'created_at' => now(),
        ]);
        return ApiResponse::success(['id' => $id], 'Created');
    }

    public function update(Request $request, int $id): \Webman\Http\Response
    {
        DB::table('ads_example')->where('id', $id)->update([
            'name' => $request->post('name'),
            'updated_at' => now(),
        ]);
        return ApiResponse::success(null, 'Updated');
    }
}
```

## Route Registration

`service/plugin/ads-api/config/route.php` 内:

**Public (認証なし):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Protected (JWT 必須):**
`auth` ミドルウェアグループ内に追加:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Rules

1. **Sort whitelist**: ソートカラムは常に検証: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Table prefix**: すべての `DB::table()` 呼び出しで `ads_` プレフィックスを使用
3. **Pagination**: 1 ページ最大 100 件、`ApiResponse::paginated()` を使用
4. **Money**: すべての値は分 (fen) 単位、コントローラーでは変換しない
5. **Error handling**: Throwable をキャッチし、`ApiResponse::error($e->getMessage())` を返す
6. **Tenant isolation**: 常に `$request->tenantId ?? 1` でフィルタ
7. **Hashids**: ID を含むオブジェクトを返すときは `ApiResponse::success()` で `encodeIds: true` を設定
