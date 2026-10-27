<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_storage\src\Storage;

class AssetController
{
    use \erik\support\ControllerTrait;

    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4'];
    protected int $maxImageSize = 5242880;   // 5 MiB
    protected int $maxVideoSize = 52428800;  // 50 MiB

    /** DB 中始终存相对路径,读取时补全本地公开 URL */
    protected function presentUrl(string $url): string
    {
        $prefix = '/uploads/assets/';
        if (!str_starts_with($url, $prefix)) return $url;
        return Storage::publicUrl(substr($url, strlen($prefix)));
    }

        /**
     * @Title("上传素材")
     * @Group("素材库")
     * @Url("/api/v1/assets/upload")
     * @Method("POST")
     */
    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        if (!$file) return ApiResponse::error('No file uploaded');

        if (!in_array($file->getUploadMimeType(), $this->allowedTypes, true)) {
            return ApiResponse::error('Unsupported file type');
        }

        $isVideo = str_starts_with($file->getUploadMimeType(), 'video/');
        $maxSize = $isVideo ? $this->maxVideoSize : $this->maxImageSize;
        // move 后 tmp 文件消失,getSize 会抛异常,须在 move 前取值
        $size = $file->getSize();
        if ($size > $maxSize) {
            return ApiResponse::error('File too large');
        }

        $ext = pathinfo($file->getUploadName(), PATHINFO_EXTENSION) ?: 'bin';
        $key = date('Ymd') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        Storage::put($key, $file);
        // DB 存相对路径,读取时补全
        $url = '/uploads/assets/' . $key;

        $mediaType = $isVideo ? 'video' : 'image';

        $id = \plugin\ads_platform\model\Campaign::snowflakeId();
        DB::table('ads_assets')->insert([
            'id'           => $id,
            'type'         => $mediaType,
            'filename'     => $file->getUploadName(),
            'mime_type'    => $file->getUploadMimeType(),
            'size'         => $size,
            'url'          => $url,
            'width'        => 0,
            'height'       => 0,
            'created_at'   => now(),
        ]);

        return ApiResponse::success(['id' => $id, 'url' => $this->presentUrl($url), 'type' => $mediaType]);
    }

        /**
     * @Title("素材列表")
     * @Group("素材库")
     * @Url("/api/v1/assets")
     * @Method("GET")
     */
    public function index(Request $request): Response
    {
        $query = DB::table('ads_assets');
        if ($type = $request->get('type')) $query->where('type', $type);

        $this->allowedSorts = ['id', 'type', 'size', 'created_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query);
        foreach ($items as &$row) {
            $row->url = $this->presentUrl((string) $row->url);
        }
        unset($row);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

        /**
     * @Title("素材详情")
     * @Group("素材库")
     * @Url("/api/v1/assets/{id}")
     * @Method("GET")
     */
    public function show(Request $request, int $id): Response
    {
        $asset = DB::table('ads_assets')->find($id);
        if (!$asset) return ApiResponse::error('Asset not found');
        $asset->url = $this->presentUrl((string) $asset->url);
        return ApiResponse::success($asset);
    }

        /**
     * @Title("删除素材")
     * @Group("素材库")
     * @Url("/api/v1/assets/{id}")
     * @Method("DELETE")
     */
    public function destroy(Request $request, int $id): Response
    {
        $asset = DB::table('ads_assets')->find($id);
        if (!$asset) return ApiResponse::error('Asset not found');

        Storage::deleteUrl((string) $asset->url);

        DB::table('ads_assets')->where('id', $id)->delete();
        return ApiResponse::success(null, 'Deleted');
    }
}
