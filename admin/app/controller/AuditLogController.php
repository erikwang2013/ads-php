<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace admin\controller;

use Illuminate\Database\Capsule\Manager as DB;
use Webman\Http\Request;
use admin\support\HashidsService;

class AuditLogController
{
    /**
     * List audit logs with pagination and filters.
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);
        $userId = $request->input('user_id', '');
        $action = $request->input('action', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo = $request->input('date_to', '');

        $query = DB::table('admin_audit_logs');

        if ($userId !== '') {
            $query->where('user_id', (int) $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $total = (clone $query)->count();
        $list = $query->orderBy('id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function ($item) {
                $hs = new HashidsService();
                $item->id = $hs->encode($item->id);
                $item->user_id = $hs->encode((int) $item->user_id);
                if ($item->detail) {
                    $item->detail = json_decode($item->detail, true);
                }
                return $item;
            });

        // Collect distinct action types for filter dropdown
        $actions = DB::table('admin_audit_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
                'actions' => $actions,
            ],
        ]);
    }
}
