# Phase 4: 大规模平台适配器扩展计划

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> 新規 17 個の広告プラットフォームアダプター（国内 7 + 海外 10）を追加

## 既存アダプター（7個）
巨量引擎、百度营销、淘宝/阿里妈妈、腾讯广告、友盟、快手磁力引擎、小红书蒲公英

## 新規国内プラットフォーム（7個）

| # | プラットフォーム | Adapterクラス | API の特徴 |
|---|------|-----------|---------|
| 17 | 微博粉丝通 | Weibo.php | OAuth2, Bearer token, 金額:分, 同期レポート |
| 18 | B站花火 | Bilibili.php | OAuth2, Bearer token, 金額:分, コンテンツマーケティング中心 |
| 19 | 优酷广告 | Youku.php | 阿里系（Taobao と同署名）, 金額:元→分 |
| 20 | 美团广告 | Meituan.php | OAuth2, Bearer token, 金額:分, ローカル生活 |
| 21 | 知乎广告 | Zhihu.php | OAuth2, Bearer token, 金額:元→分, コンテンツマーケティング |
| 22 | 360推广 | Qihoo360.php | OAuth2, API Key 署名, 金額:元→分 |
| 23 | 搜狗推广 | Sogou.php | OAuth2, API Key 署名, 金額:元→分 |

## 新規海外プラットフォーム（10個）

| # | プラットフォーム | Adapterクラス | API の特徴 |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, 金額:分(cent), システムToken |
| 25 | Amazon Ads | Amazon.php | OAuth2(Login with Amazon), Profile-based auth, 金額:分(cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, 金額:分(cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, 金額:分(cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, 金額:分(cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, 金額:分(cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, 金額:分(cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, 金額:分(cent), 限定的なAPI |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, 金額:分(cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, 金額:分(cent), カスタム署名 |

## 設計原則

すべてのアダプターは統一された PlatformAdapter インターフェースに従います。中核的な差異は以下のみ：
1. **認証方式**: OAuth2 Bearer / URL パラメータ / Header API Key+Sign / OAuth1.0a
2. **金額単位**: 統一して分（国内）/分-cent（海外）に変換し、アダプター内部でプラットフォーム差異を処理
3. **レポートモード**: 同期ページング / 非同期作成→ポーリング→取得
4. **capabilities**: 一部プラットフォームは campaign 管理をサポートせず、report のみ

## 実装戦略

プラットフォームの共通性に基づいてグループ化し、各グループ 4-5 個のアダプターを並行作成：
- **Batch A（国内 OAuth2 系）**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B（国内署名系）**: Youku, Qihoo360, Sogou
- **Batch C（国際 Meta 系）**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D（国際 DSP 系）**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

各バッチ 4-5 個のアダプターで、bootstrap.php を変更して登録します。
