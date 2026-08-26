# Phase 9: HarmonyOS 実機連携 Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase9-harmonyos.md) | [English](docs/superpowers/plans/2026-08-16-phase9-harmonyos.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase9-harmonyos.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase9-harmonyos.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase9-harmonyos.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase9-harmonyos.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase9-harmonyos.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase9-harmonyos.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase9-harmonyos.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase9-harmonyos.ja.md)

**Goal:** HarmonyOS 端の 6 ページをモックデータから実 API 呼び出し（service :8788）に切り替え、ApiClient の baseUrl ハードコード問題を修正し、ログインを実化して、鴻蒙端を利用可能な第三クライアントにします。

**出典:** Phase 7 チーム監査（mobile-dev 棚卸し：HarmonyOS 6 ページはすべてモックデータ、実呼び出し 0 箇所、ApiClient baseUrl が `http://127.0.0.1:8788/api` にハードコード）

**Tech Stack:** ArkTS + ArkUI, @ohos.net.http

---

## 現状（確認済み）

| コンポーネント | ステータス |
|---|---|
| `api/ApiClient.ets` | get/post/put/delete/login は完備；baseUrl が `http://127.0.0.1:8788/api` にハードコード（Flutter は同一生成元の相対 `/api`）；login() に呼び出し元なし |
| `pages/LoginPage.ets` | モックログイン（setTimeout 1s で遷移）、"replace with actual API call" のコメント |
| `pages/DashboardPage.ets` | `@State` にハードコードされた指標（totalCost=1250000 等） |
| `pages/CampaignListPage.ets` | L187 コメント占位 `/campaigns` |
| `pages/AccountPage.ets` | L138 コメント占位 `/accounts` |
| `pages/AlertPage.ets` | L146 コメント占位 `/alerts` |
| `pages/ReportPage.ets` | L242 コメント占位 `/reports?date=` |
| models | Campaign/PlatformAccount/AlertRule/ReportMetric は既存 |
| i18n | StringResources.ets（15+ keys） |

## Task 1: ApiClient の強化

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/api/ApiClient.ets`

### 設計ポイント
- **baseUrl を設定可能に**：setBaseUrl を維持し、デフォルト値は `http://127.0.0.1:8788/api` のまま（実機/エミュレータは LAN アドレスを指す必要がある旨をコメント）；Flutter 式の同一生成元相対パスは避ける（ArkTS は絶対 URL が必須）
- **replayHeaders の重複バグ修正**：`{ ...this.replayHeaders(), ...this.replayHeaders() }` の重複展開（get メソッド内）→ 単一に
- **login() の返却値適応**：service `POST /api/auth/login` は `{access_token, token_type, expires_in, user}` を返却（`service/plugin/ads-api/controller/v1/AuthController.php` の実フィールドと照合——token ではなく access_token、要確認のうえ `data.token` 判定を修正）
- **エラーハンドリング**：resp.responseCode が 2xx 以外の場合はエラーを投げる/明確なエラーメッセージを返す；JSON.parse 失敗の保護
- get/post/put/delete が `data.data`（ApiResponse のアンラップ）を返す既存の約束を維持

## Task 2: LoginPage の実ログイン

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/LoginPage.ets`

### 設計ポイント
- `handleLogin()` で `ApiClient.login(username, password)` を呼び出し；成功 → setToken + Dashboard へ遷移；失敗 → toast でエラー表示
- ローディング状態 isLoading は既存、再利用
- エラーメッセージは service が返す message（ApiResponse envelope）を優先、なければ汎用文面

## Task 3: 5 つの業務ページの実化

### Files:
- Modify: `apps/harmonyos/entry/src/main/ets/pages/DashboardPage.ets`、`CampaignListPage.ets`、`AccountPage.ets`、`AlertPage.ets`、`ReportPage.ets`

### エンドポイント対照（Phase 7 監査で確認済み、Flutter 修正後と一致）
| ページ | 呼び出し | 解析 |
|---|---|---|
| DashboardPage | `GET /reports/summary`（今日区間） | `data.overview` → totalCost/total_impressions/avg_ctr 等（金額は分、formatFen は既存） |
| CampaignListPage | `GET /campaigns` | `data.list`（ページング）→ Campaign model |
| AccountPage | `GET /accounts` | `data.list` → PlatformAccount model |
| AlertPage | `GET /alerts/logs` | `data.list` → AlertLog フィールド（metric/rule_name/current_value/condition/threshold/status） |
| ReportPage | `GET /reports/custom`（date_start/date_end/dimensions[]/metrics[]） | `data.list` → ReportMetric |

### 設計ポイント
- ページロード（aboutToAppear）でリクエスト発火；@State データの初期化は空/0 にしてモック値の残存を回避
- ロード失敗時はエラー表示 + リトライ（Flutter ページのエラー/リトライパターンを参考）
- 金額単位：service は分単位の数値を返却、formatFen で処理済み
- **新規ファイルは追加しない**、各ページの既存 UI 構造と i18n を維持

## Task 4: 検証

### 受入
- [ ] ApiClient に replayHeaders の重複なし、login の返却フィールドが AuthController と一致
- [ ] 6 ページにハードコードされたモック業務データが残存しない（grep で検証）
- [ ] 5 つの業務ページの呼び出しパスが service ルートと一対一で対応（`service/plugin/ads-api/config/route.php` と照合）
- [ ] ArkTS 構文検査（本環境に hvigor/DevEco ツールチェーンがあれば実行；なければ説明し手動で照合）
- [ ] 回帰：service PHPUnit は影響なし
