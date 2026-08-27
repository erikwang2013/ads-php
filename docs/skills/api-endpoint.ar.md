# مولّد نقاط النهاية API (API Endpoint Generator)

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

إضافة نقاط نهاية RESTful جديدة إلى الخدمة.

## النمط

جميع نقاط النهاية تتبع اصطلاحات RESTful، وتستخدم `app\support\ApiResponse`، ومحمية بـ JWT (إلا إذا كانت عامة).

## قالب وحدة التحكم
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

## تسجيل المسارات

في `service/plugin/ads-api/config/route.php`:

**عام (بدون مصادقة):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**محمي (يتطلب JWT):**
أضِف داخل مجموعة وسيط `auth`:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## القواعد

1. **قائمة الفرز البيضاء**: التحقق دائمًا من أعمدة الفرز: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **بادئة الجداول**: استخدام بادئة `ads_` لجميع استدعاءات `DB::table()`
3. **الترقيم**: 100 كحد أقصى لكل صفحة، واستخدام `ApiResponse::paginated()`
4. **المال**: جميع القيم بالفين (分)، بدون تحويل في وحدات التحكم
5. **معالجة الأخطاء**: التقاط Throwable، وإرجاع `ApiResponse::error($e->getMessage())`
6. **عزل المستأجر**: التصفية دائمًا بـ `$request->tenantId ?? 1`
7. **Hashids**: تعيين `encodeIds: true` في `ApiResponse::success()` عند إرجاع كائنات تحتوي على معرّفات
