# Phase 4: План масштабного расширения платформенных адаптеров

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Добавление 17 адаптеров рекламных платформ (7 отечественных + 10 зарубежных)

## Существующие адаптеры (7)
Ocean Engine (巨量引擎), Baidu Marketing, Taobao/Alimama, Tencent Ads, Umeng, Kuaishou Magnetic Engine, Xiaohongshu Bole

## Новые отечественные платформы (7)

| # | Платформа | Класс Adapter | Особенности API |
|---|------|-----------|---------|
| 17 | Weibo Fenghuo (微博粉丝通) | Weibo.php | OAuth2, Bearer token, сумма:фэнь, синхронная отчётность |
| 18 | Bilibili Huahuo (B站花火) | Bilibili.php | OAuth2, Bearer token, сумма:фэнь, в основном контент-маркетинг |
| 19 | Youku Ads | Youku.php | Семейство Alibaba (та же подпись, что у Taobao), сумма:юань→фэнь |
| 20 | Meituan Ads | Meituan.php | OAuth2, Bearer token, сумма:фэнь, локальные сервисы |
| 21 | Zhihu Ads | Zhihu.php | OAuth2, Bearer token, сумма:юань→фэнь, контент-маркетинг |
| 22 | 360 Promo (360推广) | Qihoo360.php | OAuth2, подпись API Key, сумма:юань→фэнь |
| 23 | Sogou Promo (搜狗推广) | Sogou.php | OAuth2, подпись API Key, сумма:юань→фэнь |

## Новые зарубежные платформы (10)

| # | Платформа | Класс Adapter | Особенности API |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, сумма:цент (cent), системный Token |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), авторизация на основе профиля, сумма:цент (cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, сумма:цент (cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, сумма:цент (cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, сумма:цент (cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, сумма:цент (cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, сумма:цент (cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, сумма:цент (cent), ограниченный API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, сумма:цент (cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, сумма:цент (cent), пользовательская подпись |

## Принципы проектирования

Все адаптеры следуют единому интерфейсу PlatformAdapter. Ключевые различия только в:
1. **Способе аутентификации**: OAuth2 Bearer / параметры URL / Header API Key+Sign / OAuth1.0a
2. **Единице суммы**: унифицированный перевод в фэнь (отечественные)/цент-cent (зарубежные), адаптер обрабатывает различия платформ внутри себя
3. **Режиме отчётности**: синхронная пагинация / асинхронное создание→опрос→получение
4. **capabilities**: некоторые платформы не поддерживают управление кампаниями, только отчёты

## Стратегия реализации

Группировка по общим признакам платформ, по 4-5 адаптеров в каждой группе создаются параллельно:
- **Batch A (отечественные OAuth2)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (отечественные с подписью)**: Youku, Qihoo360, Sogou
- **Batch C (международные Meta-системы)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (международные DSP)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

По 4-5 адаптеров в каждой партии, регистрация через изменение bootstrap.php.
