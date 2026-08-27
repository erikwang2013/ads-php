# Gerador de Endpoints da API

[中文](docs/skills/api-endpoint.md) | [English](docs/skills/api-endpoint.en.md) | [한국어](docs/skills/api-endpoint.ko.md) | [Русский](docs/skills/api-endpoint.ru.md) | [Deutsch](docs/skills/api-endpoint.de.md) | [Français](docs/skills/api-endpoint.fr.md) | [Español](docs/skills/api-endpoint.es.md) | [Português](docs/skills/api-endpoint.pt.md) | [हिन्दी](docs/skills/api-endpoint.hi.md) | [العربية](docs/skills/api-endpoint.ar.md) | [বাংলা](docs/skills/api-endpoint.bn.md) | [Bahasa Indonesia](docs/skills/api-endpoint.id.md) | [日本語](docs/skills/api-endpoint.ja.md)

Adicione novos endpoints RESTful ao serviço.

## Padrão

Todos os endpoints seguem as convenções RESTful, usam `app\support\ApiResponse` e são protegidos por JWT (exceto os públicos).

## Modelo de Controller
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

## Registro de Rotas

Em `service/plugin/ads-api/config/route.php`:

**Público (sem autenticação):**
```php
\Webman\Route::get('/example/public', [ExampleController::class, 'public']);
```

**Protegido (JWT obrigatório):**
Adicione dentro do grupo de middleware `auth`:
```php
\Webman\Route::get('/example', [ExampleController::class, 'index']);
\Webman\Route::post('/example', [ExampleController::class, 'store']);
\Webman\Route::get('/example/{id:\d+}', [ExampleController::class, 'show']);
\Webman\Route::put('/example/{id:\d+}', [ExampleController::class, 'update']);
```

## Regras

1. **Whitelist de ordenação**: Sempre valide as colunas de ordenação: `$allowed = ['id','name','created_at']; $sort = in_array($sort,$allowed) ? $sort : 'id';`
2. **Prefixo de tabela**: Use o prefixo `ads_` em todas as chamadas `DB::table()`
3. **Paginação**: Máximo de 100 por página, use `ApiResponse::paginated()`
4. **Dinheiro**: Todos os valores em fen (分), sem conversão nos controllers
5. **Tratamento de erros**: Capture Throwable, retorne `ApiResponse::error($e->getMessage())`
6. **Isolamento por tenant**: Sempre filtre por `$request->tenantId ?? 1`
7. **Hashids**: Defina `encodeIds: true` em `ApiResponse::success()` ao retornar objetos com IDs
