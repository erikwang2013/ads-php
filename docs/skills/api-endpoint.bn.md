# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

সার্ভিসে নতুন RESTful API এন্ডপয়েন্ট যোগ করুন।

## প্যাটার্ন

সব এন্ডপয়েন্ট RESTful কনভেনশন অনুসরণ করে, `app\support\ApiResponse` ব্যবহার করে, এবং JWT-প্রোটেক্টেড (পাবলিক না হলে)।

## কন্ট্রোলার টেমপ্লেট
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

## রাউট রেজিস্ট্রেশন

`service/plugin/ads-api/config/route.php`-এ:

**পাবলিক (কোনো auth নেই):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**প্রোটেক্টেড (JWT আবশ্যক):**
`auth` মিডলওয়্যার গ্রুপের ভিতরে যোগ করুন:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## নিয়ম

1. **সর্ট হোয়াইটলিস্ট**: সবসময় সর্ট কলাম ভ্যালিডেট করুন: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **টেবিল প্রিফিক্স**: সব `DB::table()` কলের জন্য `erik_` প্রিফিক্স ব্যবহার করুন
3. **পেজিনেশন**: প্রতি পেজ সর্বোচ্চ 100, `ApiResponse::paginated()` ব্যবহার করুন
4. **টাকা**: সব ভ্যালু ফেন (分)-এ, কন্ট্রোলারে কোনো রূপান্তর নেই
5. **এরর হ্যান্ডলিং**: Throwable ক্যাচ করুন, `ApiResponse::error($e->getMessage())` রিটার্ন করুন
6. **টেন্যান্ট আইসোলেশন**: সবসময় `$request->tenantId ?? 1` দিয়ে ফিল্টার করুন
7. **Hashids**: ID সহ অবজেক্ট রিটার্ন করার সময় `ApiResponse::success()`-এ `encodeIds: true` সেট করুন
