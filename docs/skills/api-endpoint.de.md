# API-Endpunkt-Generator

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

Neue RESTful-API-Endpunkte zum Service hinzufügen.

## Muster

Alle Endpunkte folgen RESTful-Konventionen, verwenden `app\support\ApiResponse` und sind JWT-geschützt (sofern nicht öffentlich).

## Controller-Vorlage
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

## Routenregistrierung

In `service/plugin/ads-api/config/route.php`:

**Öffentlich (ohne Auth):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Geschützt (JWT erforderlich):**
Innerhalb der `auth`-Middleware-Gruppe hinzufügen:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Regeln

1. **Sortier-Whitelist**: Sortierspalten immer validieren: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Tabellenpräfix**: `erik_`-Präfix für alle `DB::table()`-Aufrufe verwenden
3. **Paginierung**: Maximal 100 pro Seite, `ApiResponse::paginated()` verwenden
4. **Geld**: Alle Werte in Fen (分), keine Umrechnung in Controllern
5. **Fehlerbehandlung**: Throwable abfangen, `ApiResponse::error($e->getMessage())` zurückgeben
6. **Mandanten-Isolation**: Immer nach `$request->tenantId ?? 1` filtern
7. **Hashids**: `encodeIds: true` in `ApiResponse::success()` setzen, wenn Objekte mit IDs zurückgegeben werden
