# 機能設計ドキュメント

[中文](docs/features.md) | [English](docs/features.en.md) | [한국어](docs/features.ko.md) | [Русский](docs/features.ru.md) | [Deutsch](docs/features.de.md) | [Français](docs/features.fr.md) | [Español](docs/features.es.md) | [Português](docs/features.pt.md) | [हिन्दी](docs/features.hi.md) | [العربية](docs/features.ar.md) | [বাংলা](docs/features.bn.md) | [Bahasa Indonesia](docs/features.id.md) | [日本語](docs/features.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> 全 API インターフェース定義（リクエスト/レスポンス/パラメータ）は [api.ja.md](api.ja.md) を参照。

---

## モジュール総覧

| # | モジュール | コントローラー/サービス | API ルート数 | Vue ページ |
|---|------|--------|-----------|----------|
| 1 | 認証・認可 | AuthController | 3 | LoginPage |
| 2 | プラットフォーム管理 | PlatformController | 3 | — |
| 3 | アカウント管理 | AccountController | 5 | AccountList, AccountBind |
| 4 | 広告プラン | CampaignController | 6 | CampaignList |
| 5 | 広告グループ | AdGroupController | 5 | AdGroupList |
| 6 | 広告クリエイティブ | CreativeController | 2 | CreativeList |
| 7 | データレポート | DashboardController, ReportController, ExportController | 8 | DashboardPage, ReportView, ReportExport, CampaignCalendar, AttributionReport |
| 8 | アラート監視 | AlertController | 7 | AlertRuleList, AlertLogList |
| 9 | 通知センター | NotificationController | 4 | NotificationList |
| 10 | 自動入札 | BidRuleController | 5 | BidRuleList |
| 11 | ターゲティングテンプレート | TargetingTemplateController | 5 | — |
| 12 | システム管理 | AdminUserController, AuditLogController | 5 | UserManage, AuditLog |
| 13 | データ同期 | DataSyncTask, TokenRefreshTask, RetrySyncTask | — | — |
| 14 | 素材ライブラリ | AssetController | 4 | AssetGallery |
| 15 | 予算警告 | BudgetAlertService + BudgetCheckTask | 1 | — |
| 16 | 配信カレンダー | CalendarService | 1 | CampaignCalendar |
| 17 | クロスプラットフォームアトリビューション | AttributionEngine | 2 | AttributionReport |
| 18 | ヘルスチェック | HealthController | 2 | — |
| 19 | 認証コード | CaptchaController | 2 | — |
| 20 | API ドキュメント | DocController | 1 | — |

**合計**: 20 モジュール, 65+ ルート, 18 Vue ページ

---

## モジュール 1: 認証・認可

- 認証コードチェック（任意）
- `admin_users` テーブルを照会
- bcrypt `password_verify()` で検証
- JWT Token 生成 (24h TTL)
- 旧 Token を自動的にブラックリストへ追加
- Token から `uid` を抽出してユーザー情報を照会

インターフェース: ログイン / Token リフレッシュ / 現在のユーザー → [api.ja.md モジュール 2](api.ja.md#模块-2-认证)

---

## モジュール 2-3: プラットフォームとアカウント管理

- プラットフォームリストを 1 時間キャッシュ (Redis)、Season の国旗 emoji を統合
- OAuth フロー: ランダム state 生成 → 認可 URL 構築 → コールバック処理 → Token 保存
- アカウントリスト/詳細を 5 分キャッシュ

インターフェース: プラットフォームリスト / OAuth / アカウント CRUD + 同期 → [api.ja.md モジュール 3](api.ja.md#模块-3-平台--账户)

---

## モジュール 4-6: 広告配信階層

### データ構造

```
Campaign (広告プラン)
  ├── AdGroup (広告グループ) × N
  │     └── Creative (クリエイティブ) × N
  └── ReportMetrics (レポート指標)
```

- プラン作成はプラットフォームアダプター経由 + ローカル書き込み
- プラットフォーム/ステータス/キーワードでの絞り込みに対応、リストに今日の集計を含む
- 広告グループ作成は `targeting_template_id` でターゲティングテンプレートの読み込みに対応

インターフェース: プラン / 広告グループ / クリエイティブ → [api.ja.md モジュール 4-6](api.ja.md#模块-4-广告计划)

---

## モジュール 7: データレポート

- ダッシュボード集計を 5 分キャッシュ: 8 つの KPI 指標カード + 日トレンド折れ線グラフ + プラットフォーム別棒グラフ
- カスタムレポートのディメンション: date, platform, campaign
- 指標: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- エクスポート形式: CSV (UTF-8 BOM), Excel (HTML .xls), PDF (HTML プリント)

インターフェース: 集計 / カスタム / エクスポート → [api.ja.md モジュール 7](api.ja.md#模块-7-报表)

---

## モジュール 8: アラート監視

### AlertEngine 評価フロー

```
enabled=1 のルールを走査
  → ads_report_metrics を照会 (今日のデータ, scope でフィルタ)
  → compare(metric_value, threshold, condition)
  → 重複チェック (check_interval 内に既に発火 → スキップ)
  → AlertLog を作成 (status=triggered)
  → NotificationService.send()
```

### 通知チャネル

| チャネル | 状態 | 実装 |
|------|------|------|
| web | ✅ | ads_notifications に書き込み |
| email | プレースホルダー | echo スタブ |
| sms | プレースホルダー | echo スタブ |
| Redis pub/sub | ✅ | `alert:new` チャネルへ JSON プッシュ |

インターフェース: ルール CRUD / アラート記録 / 確認 / 未読数 → [api.ja.md モジュール 8](api.ja.md#模块-8-告警)

---

## モジュール 9: 通知センター

- フロントエンド Pinia store で 30s ポーリング
- サイドバーのベルアイコン + 未読数バッジ

インターフェース: リスト / 未読数 / 既読マーク / 全既読 → [api.ja.md モジュール 9](api.ja.md#模块-9-通知)

---

## モジュール 10: 自動入札エンジン

### BidEngine 評価フロー

```
enabled=1 のルールを走査
  → ads_report_metrics を照会 (今日のデータ, scope でフィルタ)
  → compare(metric_value, threshold, condition)
  → クールダウンチェック (cooldown_minutes 内に操作があったか)
  → アクションを実行:
    - adjust_budget: 新予算 = current + adjust_step, 範囲 [budget_min, budget_max]
    - toggle_pause: プランを一時停止
    - toggle_enable: プランを有効化
  → AdapterRegistry → PlatformAdapter 経由でプラットフォーム API を呼び出し
  → ローカル DB を更新 + BidLog を書き込み
```

### ルールフィールド

| フィールド | 型 | 説明 |
|------|------|------|
| metric | cost/impressions/clicks/conversions/ctr/cvr/roi | 監視指標 |
| condition | gt/gte/lt/lte | 発火条件 |
| threshold | DECIMAL(12,2) | しきい値 |
| scope | tenant/platform/campaign | 適用範囲 |
| action_type | adjust_budget/toggle_pause/toggle_enable | アクション |
| adjust_step | INT (分) | 予算調整ステップ (正=増加, 負=減少) |
| budget_min, budget_max | BIGINT | 予算境界 |
| cooldown_minutes | INT | クールダウン期間 |

インターフェース: ルール CRUD / 入札履歴 → [api.ja.md モジュール 10](api.ja.md#模块-10-自动出价)

---

## モジュール 11: オーディエンスターゲティングテンプレート

### 広告グループへの統合

```
POST /api/ad-groups が targeting_template_id に対応
→ テンプレートの targeting JSON を読み込み
→ リクエストの targeting で上書き・マージ
→ プラットフォームアダプターへ渡す
```

### 共通 JSON Schema

```json
{
  "geo": { "countries": [], "regions": [], "cities": [] },
  "age": { "min": 18, "max": 55 },
  "gender": "all",
  "interests": [],
  "behaviors": [],
  "devices": { "os": [], "types": [] },
  "languages": [],
  "placements": [],
  "custom_audiences": [],
  "lookalike_audiences": []
}
```

インターフェース: テンプレート CRUD → [api.ja.md モジュール 11](api.ja.md#模块-11-定向模板)

---

## モジュール 12: システム管理 (Admin)

- ユーザーリストの ID は hashids でエンコード
- ユーザー作成時はパスワードを bcrypt でハッシュ化
- ユーザー無効化はソフト無効化 (status=0)

監査ログフィールド: `{ user_id, username, action, resource, resource_id, detail, ip, user_agent, client_platform }`

インターフェース: ユーザー管理 / 監査ログ / ロール → [api.ja.md Admin エンドポイント](api.ja.md#admin-端点端口-8789)

---

## モジュール 13: データ同期

### DataSyncTask フロー (10 分ごと)

```
sync_enabled=1 のアカウントを走査
  → プラットフォームアダプターを取得
  → Campaigns を同期 (fetchCampaigns → updateOrInsert)
  → AdGroups を同期 (fetchAdGroups → 各 campaign を走査)
  → Creatives を同期 (fetchCreatives → 各 ad_group を走査)
  → Reports を同期 (fetchReports → 過去 2 日分 daily, 9 指標)
  → Dashboard キャッシュをクリア
  → last_sync_at を更新
```

---

## レスポンス形式

### 成功
```json
{ "code": 0, "message": "操作成功", "data": { ... } }
```

### ページネーション
```json
{ "code": 0, "message": "success", "data": { "list": [...], "pagination": { "page": 1, "per_page": 20, "total": 100, "total_pages": 5 } } }
```

### エラー
```json
{ "code": 403, "message": "Forbidden: XSS pattern detected in: q" }
```

---

## モジュール 14: 広告素材ライブラリ

- 対応タイプ: image/jpeg, image/png, image/gif, image/webp, video/mp4
- ファイル保存先: `public/uploads/assets/`
- フロントエンド: グリッドギャラリー + ドラッグ&ドロップアップロード + 画像プレビュー + 動画再生 + URL コピー

インターフェース: アップロード / リスト / 詳細 / 削除 → [api.ja.md モジュール 12](api.ja.md#模块-12-素材库)

---

## モジュール 15: 予算警告

- 3 段階アラート: yellow (≥50%), orange (≥80%), red (≥100%)
- BudgetCheckTask は 15 分ごとに実行
- 重複排除: 同一プラン・同一レベルは 1 日 1 回のみ通知
- `ads_notifications` テーブルに書き込み

インターフェース: 予算警告 → [api.ja.md モジュール 7](api.ja.md#模块-7-报表)

---

## モジュール 16: 配信カレンダー

- 日付単位で campaign スケジュールを集約
- フロントエンド Gantt 図: x 軸は日付、y 軸はプラン、プラットフォーム別に色分け
- 月/週ビューの切り替えに対応

インターフェース: 配信カレンダー → [api.ja.md モジュール 7](api.ja.md#模块-7-报表)

---

## モジュール 17: クロスプラットフォームアトリビューション

### アトリビューションモデル

| モデル | アルゴリズム |
|------|------|
| first_touch | 最初のタッチポイント 100% |
| last_touch | 最後のタッチポイント 100% |
| linear | 全タッチポイントに均等配分 (1/N) |
| time_decay | e^(-λ×Δt), 7 日半減期 |
| position_based | 先頭 40% + 末尾 40% + 中間 20% |

- 遡及ウィンドウ: 30 日
- タッチポイントのソース: `ads_report_metrics` (クリック > 0)
- 結果は `ads_attribution_results` に書き込み
- フロントエンド: AttributionReport.vue のモデル切替 + 統計カード + ECharts 棒グラフ + 明細テーブル

### データテーブル

| テーブル | フィールド |
|----|------|
| `ads_conversions` | id, tenant_id, platform, campaign_id, order_id, conversion_time, value, currency, channel |
| `ads_attribution_results` | id, tenant_id, conversion_id, model, campaign_id, credit |

インターフェース: アトリビューション分析 / モデルリスト → [api.ja.md モジュール 7](api.ja.md#模块-7-报表)

### ヘルスチェック
```json
{ "status": "healthy", "timestamp": "2026-05-21T...", "checks": { "database": "ok", "redis": "ok" } }
```

---

## モジュール 18: プラットフォーム呼び出し弾力性（サーキットブレーカー/降級）

### サーキットブレーカー状態遷移

`CircuitBreaker` (service/plugin/ads-platform/src/CircuitBreaker.php) — プラットフォーム別状態遷移:

| 状態 | トリガー | 動作 |
|------|----------|------|
| CLOSED | 正常 | 呼び出し許可 |
| OPEN | 連続5回失敗 | 即時失敗（fast-fail）、該当プラットフォームをスキップ |
| HALF_OPEN | 30秒クールダウン後 | プローブを1回許可 |
| CLOSED | プローブ成功 | 回復、カウンタリセット |
| OPEN | プローブ再失敗 | 再遮断 |

### GuardedAdapter プロキシ

- `AdapterRegistry::get()` が GuardedAdapter プロキシを返却、14 箇所の呼び出し点は無変更
- OPEN 時は `CircuitBreakerOpenException` を送出（fast-fail）、タスク層が catch して吸収 = プラットフォーム単位の降級スキップ
- Generator メソッド: 反復完了で success / 中断で failure を記録

### タイムアウト確認

- 29 アダプターすべてに CURLOPT_TIMEOUT (30/60秒) + CURLOPT_CONNECTTIMEOUT (10秒)

### テストカバレッジ

- CircuitBreakerTest 8 件 + GuardedAdapterTest 13 件

### 既知の制限

- 単一ノードの静的メモリ実装、マルチノード展開では Redis 共有状態が必要
