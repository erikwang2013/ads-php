<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;

class AssetController
{
    use \erik\support\ControllerTrait;

    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4'];
    protected int $maxImageSize = 5242880;   // 5 MiB
    protected int $maxVideoSize = 52428800;  // 50 MiB
    protected string $uploadDir = '';

    public function __construct()
    {
        $this->uploadDir = public_path() . '/uploads/assets';
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
    }

        /**
     * @Title("上传素材")
     * @Group("素材库")
     * @Url("/api/assets/upload")
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
        if ($file->getSize() > $maxSize) {
            return ApiResponse::error('File too large');
        }

        $ext = pathinfo($file->getUploadName(), PATHINFO_EXTENSION) ?: 'bin';
        $filename = date('Ymd') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $fullPath = $this->uploadDir . '/' . $filename;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $file->move($fullPath);

        $mediaType = $isVideo ? 'video' : 'image';
        $url = '/uploads/assets/' . $filename;

        $id = \plugin\ads_platform\model\Campaign::snowflakeId();
        DB::table('ads_assets')->insert([
            'id'           => $id,
            'tenant_id'    => $request->tenantId ?? 1,
            'type'         => $mediaType,
            'filename'     => $file->getUploadName(),
            'mime_type'    => $file->getUploadMimeType(),
            'size'         => $file->getSize(),
            'url'          => $url,
            'width'        => 0,
            'height'       => 0,
            'created_at'   => now(),
        ]);

        return ApiResponse::success(['id' => $id, 'url' => $url, 'type' => $mediaType]);
    }

        /**
     * @Title("素材列表")
     * @Group("素材库")
     * @Url("/api/assets")
     * @Method("GET")
     */
    public function index(Request $request): Response
    {
        $tenantId = $this->tenantId($request);
        $query = DB::table('ads_assets')->where('tenant_id', $tenantId);
        if ($type = $request->get('type')) $query->where('type', $type);

        $this->allowedSorts = ['id', 'type', 'size', 'created_at'];
        [$items, $total, $page, $perPage] = $this->paginate($request, $query);

        return ApiResponse::paginated($items, $total, $page, $perPage);
    }

        /**
     * @Title("素材详情")
     * @Group("素材库")
     * @Url("/api/assets/{id}")
     * @Method("GET")
     */
    public function show(int $id): Response
    {
        $asset = DB::table('ads_assets')->find($id);
        if (!$asset) return ApiResponse::error('Asset not found');
        return ApiResponse::success($asset);
    }

        /**
     * @Title("删除素材")
     * @Group("素材库")
     * @Url("/api/assets/{id}")
     * @Method("DELETE")
     */
    public function destroy(int $id): Response
    {
        $asset = DB::table('ads_assets')->find($id);
        if (!$asset) return ApiResponse::error('Asset not found');

        $filePath = public_path() . $asset->url;
        if (is_file($filePath)) unlink($filePath);

        DB::table('ads_assets')->where('id', $id)->delete();
        return ApiResponse::success(null, 'Deleted');
    }
}
