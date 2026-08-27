# Phase 6: Erik Stack アーキテクチャリファクタリング

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> 全面リファクタリング：データベースプレフィックス、ID 体系、暗号化体系、著作権、コード規約

## 変更リスト

| # | 変更 | パッケージ | 影響範囲 |
|---|------|----|---------|
| 1 | データベーステーブルプレフィックス `ads_` | — | すべての SQL/マイグレーションファイル |
| 2 | 主キー Snowflake ID（自動採番なし） | erikwang2013/snowflake-php | すべての Model + SQL |
| 3 | API ID hashids 暗号化/復号 | erikwang2013/hashids | すべての Controller レスポンス |
| 4 | JWT 認証切り替え | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API 機密データ暗号化 | erikwang2013/encryption | API リクエスト/レスポンス層 |
| 6 | DB 機密データ暗号化 | erikwang2013/encryptable | Eloquent Model 層 |
| 7 | ES データ同期/検索 | erikwang2013/webman-scout | レポート検索 |
| 8 | 国旗アイコン | erikwang2013/season | フロントエンドのプラットフォームタグ |
| 9 | 著作権表示 | — | すべてのファイルヘッダー |
| 10 | グローバル `\` プレフィックス除去 | — | すべての PHP ファイル |
| 11 | 設定ファイルにコメント追加 | — | config/*.php |
| 12 | Flutter Web PC レイアウト | — | Flutter プロジェクト |
| 13 | Admin パネルの可視化強化 | — | ダッシュボードチャート |
| 14 | パネルデータ PDF エクスポート | — | 新規エクスポート形式 |
| 15 | Excel エクスポート（Client+Admin） | — | エクスポート強化 |
| 16 | HarmonyOS App | — | 新規鴻蒙プロジェクト |

## 実装順序

**Batch A: インフラ（依存 + ID + 暗号化）**
- composer.json を更新して erikwang2013 の 6 パッケージを追加
- すべての SQL マイグレーションファイルを書き換え（ads_ プレフィックス + bigint 自動採番なし）
- Snowflake ID trait を作成
- すべての Model を更新（SnowflakeTrait を使用）
- hashids ミドルウェアを設定
- JWT を jwt-webman に切り替え

**Batch B: コードクリーンアップ**
- すべての `\` グローバルプレフィックスを除去
- 全ファイルに著作権ヘッダーを追加
- 設定ファイルにコメントを追加

**Batch C: フロントエンド強化**
- Admin パネルの可視化強化（チャート追加、リアルタイムデータ）
- パネルデータの PDF エクスポート
- Excel エクスポート強化

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC レイアウトプロジェクト
- HarmonyOS プロジェクトスケルトン
