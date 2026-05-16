# API Endpoint Generator

Add new RESTful API endpoints to the service.

## Pattern

All endpoints follow RESTful conventions, use `app\support\ApiResponse`, and are JWT-protected (unless public).

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
        $paginator = DB::table('erik_example')->where('tenant_id', $tenantId)->paginate($perPage);

        return ApiResponse::paginated(
            $paginator->items(), $paginator->total(),
            $paginator->currentPage(), $paginator->perPage()
        );
    }

    public function show(int $id): \Webman\Http\Response
    {
        $item = DB::table('erik_example')->find($id);
        if (!$item) return ApiResponse::error('Not found');
        return ApiResponse::success($item);
    }

    public function store(Request $request): \Webman\Http\Response
    {
        $id = DB::table('erik_example')->insertGetId([
            'name' => $request->post('name'),
            'created_at' => now(),
        ]);
        return ApiResponse::success(['id' => $id], 'Created');
    }

    public function update(Request $request, int $id): \Webman\Http\Response
    {
        DB::table('erik_example')->where('id', $id)->update([
            'name' => $request->post('name'),
            'updated_at' => now(),
        ]);
        return ApiResponse::success(null, 'Updated');
    }
}
```

## Route Registration

In `service/plugin/ads-api/config/route.php`:

**Public (no auth):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Protected (JWT required):**
Add inside the `auth` middleware group:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Rules

1. **Sort whitelist**: Always validate sort columns: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Table prefix**: Use `erik_` prefix for all `DB::table()` calls
3. **Pagination**: Max 100 per page, use `ApiResponse::paginated()`
4. **Money**: All values in fen (分), no conversion in controllers
5. **Error handling**: Catch Throwable, return `ApiResponse::error($e->getMessage())`
6. **Tenant isolation**: Always filter by `$request->tenantId ?? 1`
7. **Hashids**: Set `encodeIds: true` in `ApiResponse::success()` when returning objects with IDs
