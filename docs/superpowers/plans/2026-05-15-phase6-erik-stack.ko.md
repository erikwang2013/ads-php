# Phase 6: Erik Stack 아키텍처 리팩토링

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> 전면 리팩토링: 데이터베이스 접두사, ID 체계, 암호화 체계, 저작권, 코드 규범

## 변경 목록

| # | 변경 | 패키지 | 영향 범위 |
|---|------|----|---------|
| 1 | 데이터베이스 테이블 접두사 `erik_` | — | 모든 SQL/마이그레이션 파일 |
| 2 | 기본 키 Snowflake ID (자동 증가 없음) | erikwang2013/snowflake-php | 모든 Model + SQL |
| 3 | API ID hashids 암복호화 | erikwang2013/hashids | 모든 Controller 응답 |
| 4 | JWT 인증 전환 | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API 민감 데이터 암복호화 | erikwang2013/encryption | API 요청/응답 레이어 |
| 6 | DB 민감 데이터 암복호화 | erikwang2013/encryptable | Eloquent Model 레이어 |
| 7 | ES 데이터 동기화/조회 | erikwang2013/webman-scout | 보고서 검색 |
| 8 | 국가 플래그 | erikwang2013/season | 프론트엔드 플랫폼 태그 |
| 9 | 저작권 고지 | — | 모든 파일 헤더 |
| 10 | 전역 `\` 접두사 제거 | — | 모든 PHP 파일 |
| 11 | 설정 파일 주석 추가 | — | config/*.php |
| 12 | Flutter Web PC 레이아웃 | — | Flutter 프로젝트 |
| 13 | Admin 패널 시각화 강화 | — | 대시보드 차트 |
| 14 | 패널 데이터 PDF 내보내기 | — | 내보내기 형식 추가 |
| 15 | Excel 내보내기(Client+Admin) | — | 내보내기 강화 |
| 16 | HarmonyOS App | — | 하모니(Hongmeng) 프로젝트 신규 생성 |

## 구현 순서

**Batch A: 인프라 (의존성 + ID + 암호화)**
- composer.json 업데이트하여 erikwang2013 패키지 6개 추가
- 모든 SQL 마이그레이션 파일 재작성 (erik_ 접두사 + bigint 자동 증가 없음)
- Snowflake ID trait 생성
- 모든 Model 업데이트 (SnowflakeTrait 사용)
- hashids 미들웨어 구성
- JWT를 jwt-webman으로 전환

**Batch B: 코드 정리**
- 모든 `\` 전역 접두사 제거
- 모든 파일에 저작권 헤더 추가
- 설정 파일에 주석 추가

**Batch C: 프론트엔드 강화**
- Admin 패널 시각화 강화 (차트 확대, 실시간 데이터)
- 패널 데이터 PDF 내보내기
- Excel 내보내기 강화

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC 레이아웃 프로젝트
- HarmonyOS 프로젝트 스켈레톤
