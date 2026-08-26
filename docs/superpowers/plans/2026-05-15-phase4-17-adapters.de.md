# Phase 4: Plan zur Erweiterung um 17 Plattform-Adapter im großen Stil

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> 17 neue Werbeplattform-Adapter (7 Inland + 10 Ausland)

## Bestehende Adapter (7)
巨量引擎, 百度营销, 淘宝/阿里妈妈, 腾讯广告, 友盟, 快手磁力引擎, 小红书蒲公英

## Neue Inlandsplattformen (7)

| # | Plattform | Adapterklasse | API-Merkmale |
|---|------|-----------|---------|
| 17 | 微博粉丝通 | Weibo.php | OAuth2, Bearer token, Betrag: Fen, synchrone Berichte |
| 18 | B站花火 | Bilibili.php | OAuth2, Bearer token, Betrag: Fen, vorwiegend Content-Marketing |
| 19 | 优酷广告 | Youku.php | Alibaba-Familie (gleiche Taobao-Signatur), Betrag: Yuan→Fen |
| 20 | 美团广告 | Meituan.php | OAuth2, Bearer token, Betrag: Fen, lokales Leben |
| 21 | 知乎广告 | Zhihu.php | OAuth2, Bearer token, Betrag: Yuan→Fen, Content-Marketing |
| 22 | 360推广 | Qihoo360.php | OAuth2, API-Key-Signatur, Betrag: Yuan→Fen |
| 23 | 搜狗推广 | Sogou.php | OAuth2, API-Key-Signatur, Betrag: Yuan→Fen |

## Neue Auslandsplattformen (10)

| # | Plattform | Adapterklasse | API-Merkmale |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, Betrag: Cent, System-Token |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), Profile-basierte Auth, Betrag: Cent |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, Betrag: Cent, REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, Betrag: Cent |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, Betrag: Cent (mikro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, Betrag: Cent, Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, Betrag: Cent |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, Betrag: Cent, begrenzte API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, Betrag: Cent (mikro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, Betrag: Cent, benutzerdefinierte Signatur |

## Designprinzipien

Alle Adapter folgen dem einheitlichen PlatformAdapter-Interface. Die Kernunterschiede liegen nur in:
1. **Authentifizierung**: OAuth2 Bearer / URL-Parameter / Header API Key+Sign / OAuth1.0a
2. **Betragseinheit**: einheitlich in Fen (Inland) / Cent (Ausland) umgerechnet, der Adapter behandelt Plattformunterschiede intern
3. **Berichtsmuster**: synchrone Paginierung / asynchron Erstellen→Polling→Abrufen
4. **capabilities**: Einige Plattformen unterstützen keine Campaign-Verwaltung, nur Berichte

## Umsetzungsstrategie

Nach Gemeinsamkeiten der Plattformen gruppieren, pro Gruppe 4-5 Adapter parallel erstellen:
- **Batch A (Inland OAuth2-Serie)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (Inland Signatur-Serie)**: Youku, Qihoo360, Sogou
- **Batch C (International Meta-Serie)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (International DSP-Serie)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

Pro Batch 4-5 Adapter, Registrierung über bootstrap.php anpassen.
