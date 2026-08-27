# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

Tambahkan endpoint RESTful API baru ke layanan.

## Pola

Semua endpoint mengikuti konvensi RESTful, menggunakan `app\support\ApiResponse`, dan dilindungi JWT (kecuali publik).

## Template Controller
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

## Registrasi Rute

Di `service/plugin/ads-api/config/route.php`:

**Publik (tanpa auth):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Terproteksi (JWT wajib):**
Tambahkan di dalam grup middleware `auth`:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Aturan

1. **Whitelist sort**: Selalu validasi kolom sort: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Prefiks tabel**: Gunakan prefiks `ads_` untuk semua panggilan `DB::table()`
3. **Paginasi**: Maksimal 100 per halaman, gunakan `ApiResponse::paginated()`
4. **Uang**: Semua nilai dalam sen (分), tanpa konversi di controller
5. **Penanganan error**: Tangkap Throwable, kembalikan `ApiResponse::error($e->getMessage())`
6. **Isolasi tenant**: Selalu filter dengan `$request->tenantId ?? 1`
7. **Hashids**: Atur `encodeIds: true` di `ApiResponse::success()` saat mengembalikan objek dengan ID
