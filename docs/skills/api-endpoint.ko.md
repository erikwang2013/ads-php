# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

서비스에 새 RESTful API 엔드포인트를 추가합니다.

## 패턴

모든 엔드포인트는 RESTful 규약을 따르고 `app\support\ApiResponse`를 사용하며 JWT로 보호됩니다 (공개용 제외).

## 컨트롤러 템플릿
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

## 라우트 등록

`service/plugin/ads-api/config/route.php`에서:

**공개 (인증 없음):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**보호 (JWT 필요):**
`auth` 미들웨어 그룹 안에 추가:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## 규칙

1. **정렬 화이트리스트**: 항상 정렬 컬럼 검증: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **테이블 접두사**: 모든 `DB::table()` 호출에 `erik_` 접두사 사용
3. **페이징**: 페이지당 최대 100, `ApiResponse::paginated()` 사용
4. **금액**: 모든 값은 분(分) 단위, 컨트롤러에서 변환 금지
5. **오류 처리**: Throwable 캐치, `ApiResponse::error($e->getMessage())` 반환
6. **테넌트 격리**: 항상 `$request->tenantId ?? 1`으로 필터링
7. **Hashids**: ID가 있는 객체 반환 시 `ApiResponse::success()`에서 `encodeIds: true` 설정
