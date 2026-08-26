# Phase 4 : Plan d'extension massive des adaptateurs de plateformes

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Ajout de 17 adaptateurs de plateformes publicitaires (7 nationaux + 10 internationaux)

## Adaptateurs existants (7)
巨量引擎、百度营销、淘宝/阿里妈妈、腾讯广告、友盟、快手磁力引擎、小红书蒲公英

## Nouvelles plateformes nationales (7)

| # | Plateforme | Classe Adapter | Caractéristiques API |
|---|------|-----------|---------|
| 17 | 微博粉丝通 | Weibo.php | OAuth2, Bearer token, montant:fen, rapports synchrones |
| 18 | B站花火 | Bilibili.php | OAuth2, Bearer token, montant:fen, marketing de contenu principalement |
| 19 | 优酷广告 | Youku.php | Écosystème Alibaba (signature Taobao identique), montant:yuan→fen |
| 20 | 美团广告 | Meituan.php | OAuth2, Bearer token, montant:fen, vie locale |
| 21 | 知乎广告 | Zhihu.php | OAuth2, Bearer token, montant:yuan→fen, marketing de contenu |
| 22 | 360推广 | Qihoo360.php | OAuth2, signature API Key, montant:yuan→fen |
| 23 | 搜狗推广 | Sogou.php | OAuth2, signature API Key, montant:yuan→fen |

## Nouvelles plateformes internationales (10)

| # | Plateforme | Classe Adapter | Caractéristiques API |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, montant:fen(cent), Token système |
| 25 | Amazon Ads | Amazon.php | OAuth2(Login with Amazon), auth basée sur profil, montant:fen(cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, montant:fen(cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + en-tête Secret, DSP, montant:fen(cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, montant:fen(cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, montant:fen(cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, montant:fen(cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, montant:fen(cent), API limitée |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, montant:fen(cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, montant:fen(cent), signature personnalisée |

## Principes de conception

Tous les adaptateurs suivent l'interface unifiée PlatformAdapter. Les différences essentielles portent uniquement sur :
1. **Méthode d'authentification** : OAuth2 Bearer / paramètre URL / en-tête API Key+Sign / OAuth1.0a
2. **Unité monétaire** : conversion unifiée en fen (national)/fen-cent (international), l'adaptateur gère la différence de plateforme en interne
3. **Mode de rapport** : pagination synchrone / asynchrone création→interrogation→récupération
4. **capabilities** : certaines plateformes ne prennent pas en charge la gestion des campagnes, uniquement report

## Stratégie d'implémentation

Groupement par points communs des plateformes, 4-5 adaptateurs créés en parallèle par groupe :
- **Batch A (série OAuth2 nationale)** : Weibo, Bilibili, Meituan, Zhihu
- **Batch B (série signature nationale)** : Youku, Qihoo360, Sogou
- **Batch C (série Meta internationale)** : Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (série DSP internationale)** : Amazon, TheTradeDesk, Spotify, Twitch, Netflix

Chaque lot de 4-5 adaptateurs, avec modification de l'enregistrement dans bootstrap.php.
