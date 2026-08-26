# Phase 4: 大规模平台适配器扩展计划

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> ১৭টি নতুন অ্যাড প্ল্যাটফর্ম অ্যাডাপ্টার যোগ করুন (দেশীয় ৭ + বিদেশি ১০)

## বিদ্যমান অ্যাডাপ্টার (৭টি)
巨量引擎、百度营销、淘宝/阿里妈妈、腾讯广告、友盟、快手磁力引擎、小红书蒲公英

## নতুন দেশীয় প্ল্যাটফর্ম (৭টি)

| # | প্ল্যাটফর্ম | Adapter ক্লাস | API বৈশিষ্ট্য |
|---|------|-----------|---------|
| 17 | 微博粉丝通 | Weibo.php | OAuth2, Bearer token, অর্থ: ফেন (分), সিঙ্ক রিপোর্ট |
| 18 | B站花火 | Bilibili.php | OAuth2, Bearer token, অর্থ: ফেন (分), কনটেন্ট মার্কেটিং প্রধান |
| 19 | 优酷广告 | Youku.php | 阿里系 (Taobao-এর সাইনিংয়ের মতো), অর্থ: 元→ফেন (分) |
| 20 | 美团广告 | Meituan.php | OAuth2, Bearer token, অর্থ: ফেন (分), লোকাল লাইফ সার্ভিস |
| 21 | 知乎广告 | Zhihu.php | OAuth2, Bearer token, অর্থ: 元→ফেন (分), কনটেন্ট মার্কেটিং |
| 22 | 360推广 | Qihoo360.php | OAuth2, API Key সাইনিং, অর্থ: 元→ফেন (分) |
| 23 | 搜狗推广 | Sogou.php | OAuth2, API Key সাইনিং, অর্থ: 元→ফেন (分) |

## নতুন বিদেশি প্ল্যাটফর্ম (১০টি)

| # | প্ল্যাটফর্ম | Adapter ক্লাস | API বৈশিষ্ট্য |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, অর্থ: ফেন (cent), সিস্টেম টোকেন |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), Profile-based auth, অর্থ: ফেন (cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, অর্থ: ফেন (cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, অর্থ: ফেন (cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, অর্থ: ফেন (cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, অর্থ: ফেন (cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, অর্থ: ফেন (cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, অর্থ: ফেন (cent), সীমিত API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, অর্থ: ফেন (cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, অর্থ: ফেন (cent), কাস্টম সাইনিং |

## ডিজাইন নীতি

সব অ্যাডাপ্টার অভিন্ন PlatformAdapter ইন্টারফেস অনুসরণ করে। মূল পার্থক্য শুধু:
1. **অথেনটিকেশন পদ্ধতি**: OAuth2 Bearer / URL প্যারামিটার / Header API Key+Sign / OAuth1.0a
2. **অর্থের একক**: ইউনিফাইডভাবে ফেন (দেশীয়)/ফেন-cent (বিদেশি)-এ রূপান্তর, অ্যাডাপ্টার প্ল্যাটফর্ম পার্থক্য অভ্যন্তরীণভাবে হ্যান্ডল করে
3. **রিপোর্ট মোড**: সিঙ্ক পেজিনেশন / অ্যাসিঙ্ক তৈরি→পোলিং→প্রাপ্তি
4. **capabilities**: কিছু প্ল্যাটফর্ম campaign ম্যানেজমেন্ট সাপোর্ট করে না, শুধু report

## বাস্তবায়ন কৌশল

প্ল্যাটফর্মের সাধারণতা অনুযায়ী গ্রুপ করা হয়েছে, প্রতি গ্রুপে ৪-৫টি অ্যাডাপ্টার সমান্তরালে তৈরি:
- **Batch A (দেশীয় OAuth2 সিরিজ)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (দেশীয় সাইনিং সিরিজ)**: Youku, Qihoo360, Sogou
- **Batch C (আন্তর্জাতিক Meta সিরিজ)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (আন্তর্জাতিক DSP সিরিজ)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

প্রতি ব্যাচে ৪-৫টি অ্যাডাপ্টার, bootstrap.php-এ রেজিস্টার করুন।
