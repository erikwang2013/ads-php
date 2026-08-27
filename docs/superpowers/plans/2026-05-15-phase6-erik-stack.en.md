# Phase 6: Erik Stack Architecture Refactoring

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Full refactoring: database prefix, ID system, encryption system, copyright, coding standards

## Change List

| # | Change | Package | Scope |
|---|--------|---------|-------|
| 1 | Database table prefix `ads_` | — | All SQL/migration files |
| 2 | Snowflake ID primary keys (no auto-increment) | erikwang2013/snowflake-php | All Models + SQL |
| 3 | API ID hashids encryption/decryption | erikwang2013/hashids | All Controller responses |
| 4 | JWT authentication switch | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API sensitive data encryption/decryption | erikwang2013/encryption | API request/response layer |
| 6 | DB sensitive data encryption/decryption | erikwang2013/encryptable | Eloquent Model layer |
| 7 | ES data sync/search | erikwang2013/webman-scout | Report search |
| 8 | Country flags | erikwang2013/season | Frontend platform badges |
| 9 | Copyright notice | — | Headers of all files |
| 10 | Remove global `\` prefix | — | All PHP files |
| 11 | Add comments to config files | — | config/*.php |
| 12 | Flutter Web PC layout | — | Flutter project |
| 13 | Admin panel visualization enhancements | — | Dashboard charts |
| 14 | Panel data PDF export | — | New export format |
| 15 | Excel export (Client + Admin) | — | Enhanced export |
| 16 | HarmonyOS App | — | New HarmonyOS project |

## Implementation Order

**Batch A: Infrastructure (dependencies + ID + encryption)**
- Update composer.json to add the 6 erikwang2013 packages
- Rewrite all SQL migration files (ads_ prefix + bigint without auto-increment)
- Create the Snowflake ID trait
- Update all Models (use SnowflakeTrait)
- Configure the hashids middleware
- Switch JWT to jwt-webman

**Batch B: Code Cleanup**
- Remove all `\` global prefixes
- Add copyright headers to all files
- Add comments to config files

**Batch C: Frontend Enhancements**
- Admin panel visualization enhancements (more charts, real-time data)
- Panel data PDF export
- Excel export enhancements

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC layout project
- HarmonyOS project skeleton
