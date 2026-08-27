# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

सेवा में नए RESTful API एंडपॉइंट जोड़ें।

## पैटर्न

सभी एंडपॉइंट RESTful परंपराओं का पालन करते हैं, `app\support\ApiResponse` का उपयोग करते हैं, और JWT-संरक्षित होते हैं (सार्वजनिक को छोड़कर)।

## कंट्रोलर टेम्पलेट
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

## रूट पंजीकरण

`service/plugin/ads-api/config/route.php` में:

**सार्वजनिक (बिना प्रमाणीकरण):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**संरक्षित (JWT आवश्यक):**
`auth` मिडलवेयर ग्रुप के अंदर जोड़ें:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## नियम

1. **सॉर्ट व्हाइटलिस्ट**: हमेशा सॉर्ट कॉलम सत्यापित करें: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **टेबल प्रीफ़िक्स**: सभी `DB::table()` कॉल में `ads_` प्रीफ़िक्स का उपयोग करें
3. **पेजिनेशन**: प्रति पेज अधिकतम 100, `ApiResponse::paginated()` का उपयोग करें
4. **पैसा**: सभी मान fen (分) में, कंट्रोलर में कोई रूपांतरण नहीं
5. **त्रुटि प्रबंधन**: Throwable पकड़ें, `ApiResponse::error($e->getMessage())` लौटाएँ
6. **टेनेंट आइसोलेशन**: हमेशा `$request->tenantId ?? 1` से फ़िल्टर करें
7. **Hashids**: ID वाली ऑब्जेक्ट लौटाते समय `ApiResponse::success()` में `encodeIds: true` सेट करें
