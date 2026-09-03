<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */
namespace plugin\ads_api\controller\v1;

use Webman\Http\Request;
use Webman\Http\Response;
use app\support\ApiResponse;
use Illuminate\Database\Capsule\Manager as DB;
use plugin\ads_storage\model\CdnProvider;
use plugin\ads_storage\src\Storage;

class AssetController
{
    use \erik\support\ControllerTrait;

    protected array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4'];
    protected int $maxImageSize = 5242880;   // 5 MiB
    protected int $maxVideoSize = 52428800;  // 50 MiB

    /** 读取时按默认 provider 的 CDN 域名拼接完整 URL;DB 中始终存相对路径 */
    protected function presentUrl(string $url): string
    {
        $provider = CdnProvider::defaultProvider();
        if ($provider?->cdn_domain) {
            return 'https://' . rtrim($provider->cdn_domain, '/') . '/' . ltrim($url, '/');
        }
        return $url;
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
        // DB 存相对路径,CDN 域名读取时拼接
        $url = '/uploads/assets/' . $key;

        $mediaType = $isVideo ? 'video' : 'image';

        $id = \plugin\ads_platform\model\Campaign::snowflakeId();
        DB::table('ads_assets')->insert([
            'id'           => $id,
            'tenant_id'    => $request->tenantId ?? 1,
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
        $tenantId = $this->tenantId($request);
        $query = DB::table('ads_assets')->where('tenant_id', $tenantId);
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
        $asset = DB::table('ads_assets')->where('tenant_id', $this->tenantId($request))->find($id);
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
        $asset = DB::table('ads_assets')->where('tenant_id', $this->tenantId($request))->find($id);
        if (!$asset) return ApiResponse::error('Asset not found');

        // url→key 解析下沉到各 driver(对象存储 URL 无 /uploads/assets 前缀)
        Storage::deleteUrl((string) $asset->url);
        // 删除源文件后刷新 CDN 缓存,避免失效内容残留
        Storage::purge([$this->presentUrl((string) $asset->url)]);

        DB::table('ads_assets')->where('id', $id)->delete();
        return ApiResponse::success(null, 'Deleted');
    }

    /**
     * @Title("预签名直传地址")
     * @Group("素材库")
     * @Url("/api/v1/assets/presign")
     * @Method("POST")
     */
    public function presign(Request $request): Response
    {
        $filename = trim((string) ($request->post('filename') ?? ''));
        $mime = strtolower((string) ($request->post('mime_type') ?? ''));
        if ($filename === '' || !in_array($mime, $this->allowedTypes, true)) {
            return ApiResponse::error('filename and mime_type are required');
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
        $key = date('Ymd') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;

        try {
            $uploadUrl = Storage::signedUrl($key, 3600, $mime);
        } catch (\Throwable $e) {
            \support\Log::error('Presign failed: ' . $e->getMessage());
            return ApiResponse::error('Presigned upload not supported by current storage driver');
        }
        return ApiResponse::success([
            'key'        => $key,
            'upload_url' => $uploadUrl,
            'expires_in' => 3600,
            'url'        => $this->presentUrl('/uploads/assets/' . $key),
        ]);
    }

    /**
     * @Title("登记直传素材")
     * @Group("素材库")
     * @Url("/api/v1/assets/register")
     * @Method("POST")
     */
    public function register(Request $request): Response
    {
        $key = (string) ($request->post('key') ?? '');
        $filename = trim((string) ($request->post('filename') ?? ''));
        $mime = strtolower((string) ($request->post('mime_type') ?? ''));
        $size = (int) ($request->post('size') ?? 0);
        // 只接受本系统生成的 key 格式,杜绝路径穿越
        if (!preg_match('#^\d{8}/[0-9a-f]{32}\.[a-z0-9]{1,10}$#', $key)) {
            return ApiResponse::error('Invalid key');
        }
        if (!in_array($mime, $this->allowedTypes, true) || $size <= 0 || $filename === '') {
            return ApiResponse::error('filename, mime_type and size are required');
        }
        $isVideo = str_starts_with($mime, 'video/');

        $id = \plugin\ads_platform\model\Campaign::snowflakeId();
        DB::table('ads_assets')->insert([
            'id'         => $id,
            'tenant_id'  => $request->tenantId ?? 1,
            'type'       => $isVideo ? 'video' : 'image',
            'filename'   => $filename,
            'mime_type'  => $mime,
            'size'       => $size,
            'url'        => '/uploads/assets/' . $key,
            'width'      => 0,
            'height'     => 0,
            'created_at' => now(),
        ]);

        return ApiResponse::success([
            'id'  => $id,
            'url' => $this->presentUrl('/uploads/assets/' . $key),
            'type' => $isVideo ? 'video' : 'image',
        ], 'Created');
    }
}
