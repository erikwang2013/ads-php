<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * Shared controller helpers — reduces duplication across list endpoints.
 *
 * Usage:
 *   class FooController {
 *       use \erik\support\ControllerTrait;
 *       protected array $allowedSorts = ['id', 'name', 'created_at'];
 *       ...
 *       [$items, $total, $page, $perPage] = $this->paginate($request, $query);
 *   }
 */

namespace erik\support;

use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;

trait ControllerTrait
{
    protected array $allowedSorts = ['id', 'name', 'status', 'created_at', 'updated_at'];

    protected function tenantId(Request $request): int
    {
        return $request->tenantId ?? 1;
    }

    protected function paginate(Request $request, $query, string $table = ''): array
    {
        $sort = $request->get('sort', 'id');
        $allowed = $this->allowedSorts;
        $sort = in_array($sort, $allowed) ? $sort : 'id';
        $column = $table ? "{$table}.{$sort}" : $sort;
        $query->orderBy($column, 'desc');

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return [$paginator->items(), $paginator->total(), $paginator->currentPage(), $paginator->perPage()];
    }

    protected static array $sensitiveKeys = ['password', 'token', 'secret', 'access_token', 'refresh_token', 'authorization', 'key', 'salt'];

    protected function logError(Throwable $e): void
    {
        $req = request();
        $context = ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()];
        if ($req) {
            $inputs = array_merge($req->get() ?: [], $req->post() ?: []);
            foreach (self::$sensitiveKeys as $key) {
                foreach ($inputs as $k => $v) {
                    if (stripos((string) $k, $key) !== false) $inputs[$k] = '***';
                }
            }
            $context['inputs'] = $inputs;
            $context['path'] = $req->path();
        }
        \support\Log::channel('default')->error($e->getMessage(), $context);
    }

    protected function catchError(Throwable $e): Response
    {
        $this->logError($e);
        return \app\support\ApiResponse::error(
            env('APP_DEBUG', false) ? $e->getMessage() : 'Internal Server Error'
        );
    }
}
