# Fase 4: Plan de Ampliación Masiva de Adaptadores de Plataformas

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Añadir 17 adaptadores de plataformas publicitarias (7 nacionales + 10 internacionales)

## Adaptadores existentes (7)
Ocean Engine, Baidu Marketing, Taobao/Alimama, Tencent Ads, Umeng, Kuaishou Magnet Engine, Xiaohongshu Dandelion

## Nuevas plataformas nacionales (7)

| # | Plataforma | Clase Adapter | Características API |
|---|------|-----------|---------|
| 17 | Weibo Fans Tong | Weibo.php | OAuth2, Bearer token, moneda:céntimos, informes síncronos |
| 18 | Bilibili Huahuo | Bilibili.php | OAuth2, Bearer token, moneda:céntimos, marketing de contenidos principalmente |
| 19 | Youku Ads | Youku.php | Familia Alibaba (misma firma que Taobao), moneda:yuanes→céntimos |
| 20 | Meituan Ads | Meituan.php | OAuth2, Bearer token, moneda:céntimos, comercio local |
| 21 | Zhihu Ads | Zhihu.php | OAuth2, Bearer token, moneda:yuanes→céntimos, marketing de contenidos |
| 22 | Qihoo 360 Promoción | Qihoo360.php | OAuth2, firma API Key, moneda:yuanes→céntimos |
| 23 | Sogou Promoción | Sogou.php | OAuth2, firma API Key, moneda:yuanes→céntimos |

## Nuevas plataformas internacionales (10)

| # | Plataforma | Clase Adapter | Características API |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, moneda:céntimos(cent), Token de sistema |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), autenticación basada en perfil, moneda:céntimos(cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, moneda:céntimos(cent), API REST |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, moneda:céntimos(cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, moneda:céntimos(cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, moneda:céntimos(cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, moneda:céntimos(cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, moneda:céntimos(cent), API limitada |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, moneda:céntimos(cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, moneda:céntimos(cent), firma personalizada |

## Principios de diseño

Todos los adaptadores siguen la interfaz unificada PlatformAdapter. Las diferencias principales solo son:
1. **Autenticación**: OAuth2 Bearer / parámetro URL / Header API Key+Sign / OAuth1.0a
2. **Unidad monetaria**: conversión uniforme a céntimos (nacional) / céntimos-cent (internacional); el adaptador maneja las diferencias de la plataforma internamente
3. **Modo de informes**: paginación síncrona / creación asíncrona → sondeo → obtención
4. **capabilities**: algunas plataformas no admiten gestión de campañas, solo report

## Estrategia de implementación

Agrupar por similitud de plataformas, crear cada grupo de 4-5 adaptadores en paralelo:
- **Batch A (nacionales OAuth2)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (nacionales con firma)**: Youku, Qihoo360, Sogou
- **Batch C (internacionales tipo Meta)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (internacionales DSP)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

Cada lote de 4-5 adaptadores, modificar bootstrap.php para el registro.
