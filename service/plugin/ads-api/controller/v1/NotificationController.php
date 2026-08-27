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


class NotificationController
{
    use \erik\support\ControllerTrait;
        /**
     * @Title("通知列表")
     * @Group("通知")
     * @Url("/api/notifications")
     * @Method("GET")
     */
    public function index(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $query = DB::table('ads_notifications')->where('tenant_id', $tenantId);

        if (($type = $request->get('type')) !== null) {
            $query->where('type', $type);
        }
        if (($isRead = $request->get('is_read')) !== null) {
            $query->where('is_read', (int) $isRead);
        }

        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

        /**
     * @Title("未读通知数")
     * @Group("通知")
     * @Url("/api/notifications/unread-count")
     * @Method("GET")
     */
    public function unreadCount(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $count = DB::table('ads_notifications')
            ->where('tenant_id', $tenantId)
            ->where('is_read', 0)
            ->count();

        return ApiResponse::success(['count' => $count]);
    }

        /**
     * @Title("标记已读")
     * @Group("通知")
     * @Url("/api/notifications/{id}/read")
     * @Method("POST")
     */
    public function markRead(Request $request, int $id): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        $updated = DB::table('ads_notifications')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update(['is_read' => 1]);

        if ($updated === 0) {
            return ApiResponse::error('Notification not found');
        }

        return ApiResponse::success(null, 'Marked as read');
    }

        /**
     * @Title("全部已读")
     * @Group("通知")
     * @Url("/api/notifications/read-all")
     * @Method("POST")
     */
    public function markAllRead(Request $request): \Webman\Http\Response
    {
        $tenantId = $this->tenantId($request);
        DB::table('ads_notifications')
            ->where('tenant_id', $tenantId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return ApiResponse::success(null, 'All marked as read');
    }
}
