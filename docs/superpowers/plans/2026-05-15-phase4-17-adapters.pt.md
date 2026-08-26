# Phase 4: Plano de Expansão de Adaptadores de Plataformas em Grande Escala

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Adicionar 17 adaptadores de plataformas de publicidade (7 nacionais + 10 internacionais)

## Adaptadores existentes (7)
Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Umeng, Kuaishou Magnet Engine, Xiaohongshu Dandelion

## Novas plataformas nacionais (7)

| # | Plataforma | Classe Adapter | Características da API |
|---|------|-----------|---------|
| 17 | Weibo Fans Tong | Weibo.php | OAuth2, Bearer token, valor:centavos, relatório síncrono |
| 18 | Bilibili Huahuo | Bilibili.php | OAuth2, Bearer token, valor:centavos, foco em marketing de conteúdo |
| 19 | Youku Ads | Youku.php | Grupo Alibaba (mesma assinatura do Taobao), valor:yuan→centavos |
| 20 | Meituan Ads | Meituan.php | OAuth2, Bearer token, valor:centavos, serviços locais |
| 21 | Zhihu Ads | Zhihu.php | OAuth2, Bearer token, valor:yuan→centavos, marketing de conteúdo |
| 22 | Qihoo 360 Promotion | Qihoo360.php | OAuth2, assinatura API Key, valor:yuan→centavos |
| 23 | Sogou Promotion | Sogou.php | OAuth2, assinatura API Key, valor:yuan→centavos |

## Novas plataformas internacionais (10)

| # | Plataforma | Classe Adapter | Características da API |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, valor:centavos (cent), token do sistema |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), auth baseada em perfil, valor:centavos (cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, valor:centavos (cent), API REST |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret no header, DSP, valor:centavos (cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, valor:centavos (cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, valor:centavos (cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, valor:centavos (cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, valor:centavos (cent), API limitada |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, valor:centavos (cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, valor:centavos (cent), assinatura personalizada |

## Princípios de design

Todos os adaptadores seguem a interface unificada PlatformAdapter. As diferenças essenciais são apenas:
1. **Autenticação**: OAuth2 Bearer / parâmetro de URL / API Key+Sign no Header / OAuth1.0a
2. **Unidade monetária**: conversão unificada para centavos (nacional)/centavos-cent (internacional); o adaptador trata as diferenças de plataforma internamente
3. **Modo de relatório**: paginação síncrona / assíncrono criar→consultar→obter
4. **capabilities**: algumas plataformas não suportam gerenciamento de campanhas, apenas report

## Estratégia de implementação

Agrupar por características comuns das plataformas, criando 4-5 adaptadores por grupo em paralelo:
- **Batch A (nacionais com OAuth2)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (nacionais com assinatura)**: Youku, Qihoo360, Sogou
- **Batch C (internacionais Meta)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (internacionais DSP)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

Cada lote tem 4-5 adaptadores; modificar o bootstrap.php para registrá-los.

