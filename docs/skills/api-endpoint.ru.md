# API Endpoint Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

Добавление новых RESTful API-эндпоинтов в сервис.

## Паттерн

Все эндпоинты следуют RESTful-конвенциям, используют `app\support\ApiResponse` и защищены JWT (если не публичные).

## Шаблон контроллера
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

## Регистрация маршрута

В `service/plugin/ads-api/config/route.php`:

**Публичный (без аутентификации):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Защищенный (требуется JWT):**
Добавьте внутри группы middleware `auth`:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Правила

1. **Белый список сортировки**: всегда проверяйте столбцы сортировки: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Префикс таблиц**: используйте префикс `erik_` для всех вызовов `DB::table()`
3. **Пагинация**: максимум 100 на страницу, используйте `ApiResponse::paginated()`
4. **Деньги**: все значения в фэнях (分), без конвертации в контроллерах
5. **Обработка ошибок**: перехватывайте Throwable, возвращайте `ApiResponse::error($e->getMessage())`
6. **Изоляция арендаторов**: всегда фильтруйте по `$request->tenantId ?? 1`
7. **Hashids**: задайте `encodeIds: true` в `ApiResponse::success()` при возврате объектов с ID
