# Phase 6: Рефакторинг архитектуры Erik Stack

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Полный рефакторинг: префикс БД, система ID, система шифрования, авторские права, стандарты кода

## Список изменений

| # | Изменение | Пакет | Область влияния |
|---|------|----|---------|
| 1 | Префикс таблиц БД `ads_` | — | Все файлы SQL/миграций |
| 2 | Первичный ключ Snowflake ID (без автоинкремента) | erikwang2013/snowflake-php | Все Model + SQL |
| 3 | Хеширование/дешифрование API ID hashids | erikwang2013/hashids | Все ответы Controller |
| 4 | Переключение аутентификации JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Шифрование/дешифрование чувствительных данных API | erikwang2013/encryption | Слой запросов/ответов API |
| 6 | Шифрование/дешифрование чувствительных данных БД | erikwang2013/encryptable | Слой Eloquent Model |
| 7 | Синхронизация/поиск данных ES | erikwang2013/webman-scout | Поиск по отчётам |
| 8 | Флаги стран | erikwang2013/season | Метки платформ на фронтенде |
| 9 | Уведомление об авторских правах | — | Шапки всех файлов |
| 10 | Удаление глобального префикса `\` | — | Все PHP-файлы |
| 11 | Комментарии в файлах конфигурации | — | config/*.php |
| 12 | Макет Flutter Web PC | — | Flutter-проект |
| 13 | Улучшение визуализации Admin-панели | — | Графики на дашборде |
| 14 | Экспорт данных панели в PDF | — | Новый формат экспорта |
| 15 | Экспорт в Excel (Client+Admin) | — | Улучшенный экспорт |
| 16 | HarmonyOS App | — | Новый проект HarmonyOS |

## Порядок реализации

**Batch A: Инфраструктура (зависимости + ID + шифрование)**
- Обновить composer.json, добавить 6 пакетов erikwang2013
- Переписать все файлы SQL-миграций (префикс ads_ + bigint без автоинкремента)
- Создать Snowflake ID trait
- Обновить все Model (использование SnowflakeTrait)
- Настроить hashids middleware
- Переключить JWT на jwt-webman

**Batch B: Очистка кода**
- Удалить все глобальные префиксы `\`
- Добавить заголовок авторских прав во все файлы
- Добавить комментарии в файлы конфигурации

**Batch C: Улучшение фронтенда**
- Улучшение визуализации Admin-панели (больше графиков, данные в реальном времени)
- Экспорт данных панели в PDF
- Улучшение экспорта в Excel

**Batch D: Flutter + HarmonyOS**
- Проект макета Flutter Web PC
- Скелет проекта HarmonyOS
