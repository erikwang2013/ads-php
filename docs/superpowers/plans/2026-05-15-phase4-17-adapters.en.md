# Phase 4: Large-Scale Platform Adapter Expansion Plan

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Add 17 ad platform adapters (7 domestic + 10 international)

## Existing Adapters (7)
Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Umeng, Kuaishou (Kwai), Xiaohongshu (RED)

## New Domestic Platforms (7)

| # | Platform | Adapter Class | API Characteristics |
|---|----------|---------------|---------------------|
| 17 | Weibo Fans Connect | Weibo.php | OAuth2, Bearer token, money: fen, sync reports |
| 18 | Bilibili Huahuo | Bilibili.php | OAuth2, Bearer token, money: fen, content marketing focus |
| 19 | Youku Ads | Youku.php | Alibaba ecosystem (same as Taobao signing), money: yuan→fen |
| 20 | Meituan Ads | Meituan.php | OAuth2, Bearer token, money: fen, local life |
| 21 | Zhihu Ads | Zhihu.php | OAuth2, Bearer token, money: yuan→fen, content marketing |
| 22 | Qihoo 360 | Qihoo360.php | OAuth2, API Key signing, money: yuan→fen |
| 23 | Sogou | Sogou.php | OAuth2, API Key signing, money: yuan→fen |

## New International Platforms (10)

| # | Platform | Adapter Class | API Characteristics |
|---|----------|---------------|---------------------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, money: fen (cent), system token |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), Profile-based auth, money: fen (cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, money: fen (cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, money: fen (cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, money: fen (cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, money: fen (cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, money: fen (cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, money: fen (cent), limited API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, money: fen (cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, money: fen (cent), custom signing |

## Design Principles

All adapters follow the unified PlatformAdapter interface. Core differences are limited to:
1. **Auth**: OAuth2 Bearer / URL param / Header API Key+Sign / OAuth1.0a
2. **Money unit**: unified to fen (domestic) / fen-cent (international), adapters handle platform differences internally
3. **Report mode**: sync pagination / async create→poll→fetch
4. **capabilities**: some platforms do not support campaign management, report only

## Implementation Strategy

Group adapters by commonality, creating 4-5 adapters in parallel per batch:
- **Batch A (domestic OAuth2)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (domestic signing)**: Youku, Qihoo360, Sogou
- **Batch C (international Meta-style)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (international DSP)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

4-5 adapters per batch, modifying bootstrap.php to register.
