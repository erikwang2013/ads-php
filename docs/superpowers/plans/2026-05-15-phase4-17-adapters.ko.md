# Phase 4: 대규모 플랫폼 어댑터 확장 계획

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> 광고 플랫폼 어댑터 17개 추가 (국내 7 + 해외 10)

## 기존 어댑터 (7개)
巨量引擎(줄량 엔진), 바이두 마케팅, 타오바오/阿里妈妈(알리마마), 텐센트 광고, 우맹(友盟), 콰이서우 마그네틱 엔진, 샤오홍슈 민들레

## 신규 국내 플랫폼 (7개)

| # | 플랫폼 | Adapter 클래스 | API 특징 |
|---|------|-----------|---------|
| 17 | 웨이보 팬스통(粉丝通) | Weibo.php | OAuth2, Bearer token, 금액:分(펀), 동기 보고서 |
| 18 | 빌리빌리 화훠(花火) | Bilibili.php | OAuth2, Bearer token, 금액:分(펀), 콘텐츠 마케팅 위주 |
| 19 | 유쿠 광고 | Youku.php | 알리 계열(Taobao 서명과 동일), 금액:元→分 |
| 20 | 메이퇀 광고 | Meituan.php | OAuth2, Bearer token, 금액:分(펀), 로컬 생활 서비스 |
| 21 | 즈후 광고 | Zhihu.php | OAuth2, Bearer token, 금액:元→分, 콘텐츠 마케팅 |
| 22 | 360 추천 | Qihoo360.php | OAuth2, API Key 서명, 금액:元→分 |
| 23 | 소우거우 추천 | Sogou.php | OAuth2, API Key 서명, 금액:元→分 |

## 신규 해외 플랫폼 (10개)

| # | 플랫폼 | Adapter 클래스 | API 특징 |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, 금액:分(cent), 시스템 Token |
| 25 | Amazon Ads | Amazon.php | OAuth2(Login with Amazon), Profile 기반 인증, 금액:分(cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, 금액:分(cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, 금액:分(cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, 금액:分(cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, 금액:分(cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, 금액:分(cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, 금액:分(cent), 제한적 API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, 금액:分(cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, 금액:分(cent), 커스텀 서명 |

## 설계 원칙

모든 어댑터는 통일된 PlatformAdapter 인터페이스를 따릅니다. 핵심 차이는 다음뿐입니다:
1. **인증 방식**: OAuth2 Bearer / URL 파라미터 / Header API Key+Sign / OAuth1.0a
2. **금액 단위**: 통일하여 분(국내)/cent(해외)으로 변환, 어댑터 내부에서 플랫폼 차이 처리
3. **보고서 모드**: 동기 페이지네이션 / 비동기 생성→폴링→조회
4. **capabilities**: 일부 플랫폼은 campaign 관리를 지원하지 않으며 report만 가능

## 구현 전략

플랫폼 공통점별로 그룹화하여, 각 그룹 4-5개 어댑터를 병렬 생성:
- **Batch A (국내 OAuth2 계열)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (국내 서명 계열)**: Youku, Qihoo360, Sogou
- **Batch C (국제 Meta 계열)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (국제 DSP 계열)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

각 배치의 4-5개 어댑터 생성 후 bootstrap.php에 등록합니다.
