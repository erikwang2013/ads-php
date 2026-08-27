# Phase 6: Erik Stack Architecture Refactoring

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> সম্পূর্ণ রিফ্যাক্টরিং: ডাটাবেস প্রিফিক্স, ID সিস্টেম, এনক্রিপশন সিস্টেম, কপিরাইট, কোড কনভেনশন

## পরিবর্তনের তালিকা

| # | পরিবর্তন | প্যাকেজ | প্রভাবের পরিধি |
|---|------|----|---------|
| 1 | ডাটাবেস টেবিল প্রিফিক্স `ads_` | — | সব SQL/মাইগ্রেশন ফাইল |
| 2 | প্রাইমারি কী Snowflake ID (কোনো অটো-ইনক্রিমেন্ট নেই) | erikwang2013/snowflake-php | সব Model + SQL |
| 3 | API ID hashids এনক্রিপশন/ডিক্রিপশন | erikwang2013/hashids | সব Controller রেসপন্স |
| 4 | JWT অথেনটিকেশন পরিবর্তন | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API সংবেদনশীল ডেটা এনক্রিপশন/ডিক্রিপশন | erikwang2013/encryption | API রিকোয়েস্ট/রেসপন্স লেয়ার |
| 6 | DB সংবেদনশীল ডেটা এনক্রিপশন/ডিক্রিপশন | erikwang2013/encryptable | Eloquent Model লেয়ার |
| 7 | ES ডেটা সিঙ্ক/কোয়েরি | erikwang2013/webman-scout | রিপোর্ট সার্চ |
| 8 | দেশের পতাকা | erikwang2013/season | ফ্রন্টএন্ড প্ল্যাটফর্ম ট্যাগ |
| 9 | কপিরাইট নোটিশ | — | সব ফাইলের হেডার |
| 10 | গ্লোবাল `\` প্রিফিক্স অপসারণ | — | সব PHP ফাইল |
| 11 | কনফিগ ফাইলে কমেন্ট যোগ | — | config/*.php |
| 12 | Flutter Web PC লেআউট | — | Flutter প্রজেক্ট |
| 13 | Admin প্যানেল ভিজ্যুয়ালাইজেশন এনহান্সমেন্ট | — | ড্যাশবোর্ড চার্ট |
| 14 | প্যানেল ডেটা PDF এক্সপোর্ট | — | নতুন এক্সপোর্ট ফরম্যাট |
| 15 | Excel এক্সপোর্ট (Client+Admin) | — | এক্সপোর্ট এনহান্সমেন্ট |
| 16 | HarmonyOS App | — | নতুন হারমোনি প্রজেক্ট |

## বাস্তবায়নের ক্রম

**Batch A: ইনফ্রাস্ট্রাকচার (ডিপেনডেন্সি + ID + এনক্রিপশন)**
- composer.json আপডেট করে ৬টি erikwang2013 প্যাকেজ যোগ করুন
- সব SQL মাইগ্রেশন ফাইল পুনরায় লেখা (ads_ প্রিফিক্স + bigint কোনো অটো-ইনক্রিমেন্ট নেই)
- Snowflake ID trait তৈরি করুন
- সব Model আপডেট করুন (SnowflakeTrait ব্যবহার)
- hashids মিডলওয়্যার কনফিগ করুন
- JWT থেকে jwt-webman-এ পরিবর্তন করুন

**Batch B: কোড ক্লিনআপ**
- সব `\` গ্লোবাল প্রিফিক্স অপসারণ
- সব ফাইলে কপিরাইট হেডার যোগ
- কনফিগ ফাইলে কমেন্ট যোগ

**Batch C: ফ্রন্টএন্ড এনহান্সমেন্ট**
- Admin প্যানেল ভিজ্যুয়ালাইজেশন এনহান্সমেন্ট (আরও চার্ট, রিয়েল-টাইম ডেটা)
- প্যানেল ডেটা PDF এক্সপোর্ট
- Excel এক্সপোর্ট এনহান্সমেন্ট

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC লেআউট প্রজেক্ট
- HarmonyOS প্রজেক্ট স্কেলেটন
