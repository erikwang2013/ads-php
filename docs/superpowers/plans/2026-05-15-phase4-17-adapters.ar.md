# المرحلة 4: خطة توسيع محولات المنصات على نطاق واسع

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> إضافة 17 محولًا جديدًا لمنصات الإعلانات (7 محلية + 10 أجنبية)

## المحولات الحالية (7)
巨量引擎، 百度营销، 淘宝/阿里妈妈، 腾讯广告، 友盟، 快手磁力引擎، 小红书蒲公英

## المنصات المحلية الجديدة (7)

| # | المنصة | فئة المحول | خصائص API |
|---|------|-----------|---------|
| 17 | ويبو فين تونغ (微博粉丝通) | Weibo.php | OAuth2، Bearer token، المبلغ: فين، تقارير متزامنة |
| 18 | بيليبيلي هوا هو (B站花火) | Bilibili.php | OAuth2، Bearer token، المبلغ: فين، تسويق المحتوى بشكل أساسي |
| 19 | إعلانات يوكو (优酷广告) | Youku.php | نظام علي بابا (توقيع Taobao نفسه)، المبلغ: يوان→فين |
| 20 | إعلانات ميتوان (美团广告) | Meituan.php | OAuth2، Bearer token، المبلغ: فين، الحياة المحلية |
| 21 | إعلانات زيهو (知乎广告) | Zhihu.php | OAuth2، Bearer token، المبلغ: يوان→فين، تسويق المحتوى |
| 22 | ترويج 360 | Qihoo360.php | OAuth2، توقيع API Key، المبلغ: يوان→فين |
| 23 | ترويج سوجو (搜狗推广) | Sogou.php | OAuth2، توقيع API Key، المبلغ: يوان→فين |

## المنصات الأجنبية الجديدة (10)

| # | المنصة | فئة المحول | خصائص API |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2، Graph API، المبلغ: سنت، Token النظام |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon)، مصادقة قائمة على الملف الشخصي، المبلغ: سنت |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2، Bearer token، المبلغ: سنت، REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + ترويسة Secret، DSP، المبلغ: سنت |
| 28 | Snapchat Ads | Snapchat.php | OAuth2، Bearer token، المبلغ: سنت (ميكرو) |
| 29 | Spotify Ads | Spotify.php | OAuth2، Bearer token، المبلغ: سنت، صوتي |
| 30 | Twitch Ads | Twitch.php | OAuth2، Bearer token، المبلغ: سنت |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials، المبلغ: سنت، API محدود |
| 32 | Pinterest Ads | Pinterest.php | OAuth2، Bearer token، المبلغ: سنت (ميكرو) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2، المبلغ: سنت، توقيع مخصص |

## مبادئ التصميم

تتبع جميع المحولات واجهة PlatformAdapter الموحدة. الاختلافات الجوهرية فقط في:
1. **طريقة المصادقة**: OAuth2 Bearer / معلمات URL / Header API Key+Sign / OAuth1.0a
2. **وحدة المبلغ**: التحويل الموحد إلى فين (محلي) / سنت (أجنبي)، والمعالجة الداخلية لاختلافات المنصات
3. **وضع التقارير**: ترقيم متزامن / إنشاء غير متزامن → استطلاع → جلب
4. **capabilities**: بعض المنصات لا تدعم إدارة الحملات، تقارير فقط

## استراتيجية التنفيذ

التجميع حسب القواسم المشتركة للمنصات، 4-5 محولات لكل مجموعة تُنشأ بالتوازي:
- **المجموعة A (نظام OAuth2 المحلي)**: Weibo, Bilibili, Meituan, Zhihu
- **المجموعة B (نظام التوقيع المحلي)**: Youku, Qihoo360, Sogou
- **المجموعة C (نظام Meta الدولي)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **المجموعة D (نظام DSP الدولي)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

4-5 محولات لكل دفعة، مع تعديل bootstrap.php للتسجيل.
