# webman-scout

基于 [Laravel Scout](https://laravel.com/docs/scout) 并参考 [shopwwi/webman-scout](https://github.com/shopwwi/webman-scout) 的 Webman 全文搜索扩展，兼容 Webman 框架。

因 shopwwi/webman-scout 更新停滞，为满足业务中对 **时间范围查询、数据聚合** 以及 **OpenSearch** 的支持，本扩展在保持相同基础用法的同时，增加了高级查询、聚合、分面、向量检索、地理距离等能力。

## 特性

- 与 Laravel Scout / shopwwi webman-scout 用法一致，易于迁移
- 支持多种引擎：**OpenSearch**、Elasticsearch、Meilisearch、Typesense、Algolia、XunSearch、Database、Collection
- 默认面向 **OpenSearch**，支持复杂查询、聚合、KNN 向量检索
- 支持队列同步索引（可选）
- 支持索引映射更新、软删除、分块导入等

## 环境要求

- PHP >= 8.0
- Webman 框架
- Illuminate 组件 ^9.0|^10.0|^11.0|^12.0（bus、contracts、database、http、pagination、queue、support）

## 安装

```bash
composer require erikwang2013/webman-scout
```

安装后执行插件安装（会复制配置与队列消费者到项目）：

- 配置目录：`config/plugin/erikwang2013/webman-scout/`
- 队列消费者：`app/queue/redis/search/`

## 配置

在 `config/plugin/erikwang2013/webman-scout/app.php` 中配置：

| 配置项 | 说明 |
|--------|------|
| `driver` | 默认搜索引擎：`opensearch`、`elasticsearch`、`meilisearch`、`typesense`、`algolia`、`database`、`collection`、`null` 等 |
| `prefix` | 索引名称前缀 |
| `queue` | 是否使用队列同步索引 |
| `chunk.searchable` / `chunk.unsearchable` | 批量导入/移除时的分块大小 |
| `soft_delete` | 是否在索引中保留软删除记录 |

### OpenSearch 配置示例

```php
'opensearch' => [
    'host' => getenv('OPENSEARCH_HTTP_HOST', 'https://127.0.0.1:6205'),
    'username' => getenv('OPENSEARCH_USERNAME', 'admin'),
    'password' => getenv('OPENSEARCH_PASSWORD', 'admin'),
    'prefix' => getenv('OPENSEARCH_INDEX_PREFIX'),
    'ssl_verification' => (bool) getenv('OPENSEARCH_SSL_VERIFICATION', false),
    'indices' => [
        'products' => [
            'settings' => [ /* index settings */ ],
            'mappings' => [
                'properties' => [
                    'vector' => ['type' => 'knn_vector', 'dimension' => 1536],
                    'location' => ['type' => 'geo_point'],
                ],
            ],
        ],
    ],
],
```

## 模型配置

在需要被检索的模型中引入 `Searchable` trait，并可按需重写以下方法：

```php
use Erikwang2013\WebmanScout\Searchable;
use support\Model;

class Product extends Model
{
    use Searchable;

    /**
     * 索引名称（默认：配置 prefix + 表名）
     */
    public function searchableAs(): string
    {
        return 'products';
    }

    /**
     * 写入索引的数组，默认 toArray()
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'price' => $this->price,
            'created_at' => $this->created_at?->timestamp,
            'location' => ['lat' => $this->lat, 'lon' => $this->lng],
            'vector' => $this->embedding ?? [],
        ];
    }

    /**
     * 全文检索字段（用于 fulltextSearch 等）
     */
    public function searchableFields(): array
    {
        return ['title', 'content'];
    }
}
```

## 基础使用

### 简单搜索

```php
// 关键词搜索
$products = Product::search('手机')->get();

// 带回调，对 Builder 进行约束
$products = Product::search('手机', function ($builder) {
    $builder->where('status', 1);
})->get();

// 分页
$paginator = Product::search('手机')->paginate(15);
```

### 条件与排序

```php
Product::search('关键词')
    ->where('status', 1)
    ->whereIn('category_id', [1, 2, 3])
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();
```

### 同步/移除索引

```php
// 单条写入索引（同步）
$product->searchableSync();

// 单条写入索引（走队列，若开启 queue）
$product->searchable();

// 单条从索引移除
$product->unsearchable();

// 全量导入
Product::makeAllSearchable();

// 清空该模型索引
Product::removeAllFromSearch();
```

### 临时关闭同步

```php
Product::withoutSyncingToSearch(function () {
    Product::query()->where('id', 1)->update(['title' => '新标题']);
});
```

## 高级用法（扩展方法）

在 `search()` 返回的 Builder 上链式调用以下方法（主要面向 OpenSearch/Elasticsearch 等引擎）。

### 范围查询

```php
// 时间或数值范围，$inclusive 为 true 表示含边界
Product::search('')
    ->whereRange('created_at', ['gte' => 1609459200, 'lte' => 1640995200], true)
    ->whereRange('price', ['gte' => 100, 'lt' => 500])
    ->get();
```

### 地理距离

```php
// 距某经纬度 radius 范围内的结果（单位与引擎配置一致，如 km）
Product::search('')
    ->whereGeoDistance('location', 31.23, 121.47, 10.0)
    ->get();
```

### 全文检索（可指定字段与选项）

```php
Product::search('')
    ->fulltextSearch('关键词', ['title', 'content'], ['operator' => 'and'])
    ->get();
```

### 向量相似度排序（KNN）

```php
Product::search('')
    ->orderByVectorSimilarity([0.1, -0.2, ...], 'vector')  // 第二参数为向量字段名，默认取模型/引擎约定
    ->get();
```

### 聚合与分面

```php
$builder = Product::search('关键词')
    ->aggregate('price_ranges', 'range', 'price', ['ranges' => [
        ['from' => 0, 'to' => 100],
        ['from' => 100, 'to' => 500],
    ]])
    ->facet('category_id', ['size' => 10]);

$results = $builder->get();
$aggregations = $builder->getAggregations();
$facets = $builder->getFacets();
```

### 结果后处理

```php
Product::search('关键词')
    ->addResultProcessor(function ($results) {
        // 对检索结果进行二次处理
        return $results->map(fn ($item) => $item);
    })
    ->get();
```

### 索引映射更新（OpenSearch）

```php
$engine = app(\Erikwang2013\WebmanScout\EngineManager::class)->engine();
$engine->updateIndexMappings('products', [
    'properties' => [
        'new_field' => ['type' => 'keyword'],
    ],
]);
```

### 清除高级条件

在同一 Builder 上复用或重置高级条件时使用：

```php
$builder = Product::search('关键词');
$builder->whereRange('created_at', $range)->get();
$builder->clearAdvancedConditions();  // 清空 向量/高级 where/排序/聚合/分面/结果处理器
```

## 命令行

| 命令 | 说明 |
|------|------|
| `php webman scout:import [Model]` | 将指定模型全量导入搜索索引，支持 `--chunk`、`--fresh` |
| `php webman scout:flush [Model]` | 从索引中清空该模型数据 |
| `php webman scout:delete-index [Model]` | 删除该模型对应索引 |
| `php webman scout:index` | 列出可用的 Scout 索引（依实现而定） |
| `php webman scout:queue-import` | 通过队列消费进行导入（需配置队列） |
| `php webman scout:sync-index-settings` | 同步索引配置（如 OpenSearch 的 settings/mappings） |
| `php webman scout:delete-all-indexes` | 删除所有 Scout 管理的索引（慎用） |

具体参数以各命令 `--help` 为准。

## 队列

在配置中开启 `queue => true` 后，模型的 `searchable()` / `unsearchable()` 会通过队列异步写入或删除索引，需确保：

- 已安装并配置 webman redis 队列
- 已启动队列消费，并消费 `app/queue/redis/search` 下与 Scout 相关的队列（如 `scout_make`、`scout_remove`）

## API 速查（扩展方法）

| 方法 | 说明 |
|------|------|
| `whereRange(string $field, array $range, bool $inclusive = true)` | 范围查询 |
| `whereGeoDistance(string $field, float $lat, float $lng, float $radius)` | 地理距离过滤 |
| `fulltextSearch(string $query, array $fields = [], array $options = [])` | 全文检索 |
| `orderByVectorSimilarity(array $vector, ?string $vectorField = null)` | 按向量相似度排序 |
| `aggregate(string $name, string $type, string $field, array $options = [])` | 聚合 |
| `facet(string $field, array $options = [])` | 分面 |
| `addResultProcessor(callable $processor)` | 结果后处理 |
| `getAggregations()` | 获取聚合结果 |
| `getFacets()` | 获取分面结果 |
| `getVectorSearch()` | 获取向量检索配置 |
| `getAdvancedWheres()` | 获取高级 where 条件 |
| `getSorts()` | 获取排序配置 |
| `getAggregationConfig()` | 获取聚合配置 |
| `getFacetConfig()` | 获取分面配置 |
| `clearAdvancedConditions()` | 清空高级条件 |

索引映射更新由引擎提供：`updateIndexMappings(string $index, array $mappings)`（如 OpenSearchEngine）。

## 参考

- [Laravel Scout 文档](https://laravel.com/docs/scout)
- [shopwwi/webman-scout](https://github.com/shopwwi/webman-scout)

## License

MIT
