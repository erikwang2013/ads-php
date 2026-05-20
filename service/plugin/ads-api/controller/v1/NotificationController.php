<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use app\support\ApiResponse;
use Webman\Http\Response;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;


use \erik\support\ControllerTrait;

class NotificationController
{
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $query = DB::table('erik_notifications')->where('tenant_id', $tenantId);

        if (($type = $request->get('type')) !== null) {
            $query->where('type', $type);
        }
        if (($isRead = $request->get('is_read')) !== null) {
            $query->where('is_read', (int) $isRead);
        }

        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

    public function unreadCount(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $count = DB::table('erik_notifications')
            ->where('tenant_id', $tenantId)
            ->where('is_read', 0)
            ->count();

        return ApiResponse::success(['count' => $count]);
    }

    public function markRead(Request $request, int $id): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $updated = DB::table('erik_notifications')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['is_read' => 1]);

        if ($updated === 0) {
            return ApiResponse::error('Notification not found');
        }

        return ApiResponse::success(null, 'Marked as read');
    }

    public function markAllRead(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        DB::table('erik_notifications')
            ->where('tenant_id', $tenantId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return ApiResponse::success(null, 'All marked as read');
    }
}
