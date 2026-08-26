# Phase 8: अलर्ट मल्टी-चैनल लागू करना Implementation Plan

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**लक्ष्य:** Phase 5 का बचा हुआ अंतराल भरें — `NotificationService` के email/sms चैनल को echo स्टब से वास्तविक कार्यान्वयन में अपग्रेड करें (SMTP मेल + सामान्य Webhook), और चैनल कॉन्फ़िगरेशन का समर्थन करें। web चैनल और Redis pub/sub पहले से लागू हैं, अपरिवर्तित रहते हैं।

**स्रोत:** Phase 7 टीम ऑडिट निष्कर्ष (researcher योजना तुलना: एकमात्र स्पष्ट "आंशिक रूप से पूर्ण" आइटम = Phase 5 अलर्ट मल्टी-चैनल, `ads-alert` में `channel/` डायरेक्टरी नहीं है)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## वर्तमान स्थिति (सत्यापित)

| घटक | स्थिति |
|---|---|
| `NotificationService::send()` | `match ($channel)` web/email/sms वितरण; web वास्तव में `erik_notifications` में लिखता है, email/sms echo स्टब हैं |
| `AlertRule.channels` | JSON फ़ील्ड + Eloquent cast array, फ़्रंटएंड पहले से `['web','email','sms']` सबमिट करता है |
| Admin AlertRuleList.vue | चैनल चयन UI पहले से है (web लॉक्ड, email/sms वैकल्पिक) |
| Redis pub/sub | `alert:new` चैनल पुश लागू |
| SMTP/मेल कॉन्फ़िग | नहीं (service/config में कोई mail कॉन्फ़िग नहीं) |

## Task 1: मेल चैनल (SMTP)

### Files:
- Create: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, env-संचालित)
- Create: `service/plugin/ads-alert/service/channel/EmailChannel.php` (send(AlertLog, AlertRule) लागू करें)
- Modify: `service/plugin/ads-alert/service/NotificationService.php` (email शाखा EmailChannel कॉल करे, echo स्टब हटाएँ)
- Modify: `service/composer.json` (PHPMailer चुनने पर निर्भरता जोड़नी होगी; हल्का रखने के लिए बिना निर्भरता वाले `mail()`/socket कार्यान्वयन को प्राथमिकता दें, कार्यान्वयनकर्ता मूल्यांकन करे)

### डिज़ाइन मुख्य बिंदु
- प्राप्तकर्ता: AlertRule कॉन्फ़िग या टेनेंट कॉन्फ़िग से पढ़ें (न हो तो `email` फ़ील्ड या कॉन्फ़िग डिफ़ॉल्ट)
- विषय/बॉडी: sendWeb का टेक्स्ट टेम्पलेट पुनः उपयोग करें ("告警触发: {rule.name}" + मेट्रिक/वर्तमान मान/शर्त/थ्रेशोल्ड)
- विफलता हैंडलिंग: अपवाद कैप्चर कर लॉग करें, अन्य चैनलों और मुख्य फ़्लो को प्रभावित न करें
- कॉन्फ़िग न होने पर सुंदर डिग्रेडेशन (log संकेत, अपवाद फेंककर रोकें नहीं)

## Task 2: Webhook चैनल

### Files:
- Create: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (कॉन्फ़िगर किए गए URL पर POST JSON)
- Modify: `NotificationService::send()` match में `'webhook'` शाखा जोड़ें

### डिज़ाइन मुख्य बिंदु
- कॉन्फ़िग स्रोत: AlertRule विस्तार `webhook_url` फ़ील्ड (migration) या channels कॉन्फ़िग; न्यूनतम बदलाव के लिए AlertRule में `webhook_url` कॉलम जोड़ें (nullable)
- पेलोड: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, अलर्ट स्तर/मेट्रिक/मान/थ्रेशोल्ड/समय सहित
- टाइमआउट और रीट्राय: कनेक्शन टाइमआउट 5s, कुल टाइमआउट 10s, विफलता पर लॉग (रीट्राय नहीं, सरल रखें)
- सुरक्षा: केवल http/https अनुमत, इंट्रानेट पता सत्यापन नहीं (SSRF जोखिम ज्ञात सीमा के रूप में नोट करें, या गैर-इंट्रानेट सत्यापित करें — कार्यान्वयनकर्ता मूल्यांकन और रिकॉर्ड करे)

## Task 3: SMS चैनल (गेटवे प्लेसहोल्डर)

### Files:
- Modify: `NotificationService::sendSms` (प्लेसहोल्डर रखें, इंटीग्रेशन बिंदु स्पष्ट टिप्पणी; कार्यान्वयनकर्ता मूल्यांकन में हल्का समाधान हो तो लागू किया जा सकता है)

### डिज़ाइन मुख्य बिंदु
- SMS गेटवे (अलीक्लाउड/टेनसेंट क्लाउड) AK/SK और भुगतान आवश्यक, इस चरण में प्लेसहोल्डर कार्यान्वयन रखें, टिप्पणी में इंटीग्रेशन चरण बताएँ
- फ़्रंटएंड UI का sms विकल्प चयन योग्य रहे लेकिन बैकएंड केवल लॉग करे (उपयोगकर्ता को स्पष्ट बताएँ कि गेटवे कॉन्फ़िगर नहीं)

## Task 4: चैनल कॉन्फ़िगरेशन और फ़्रंटएंड

### Files:
- Modify: `admin/public/web/src/views/alert/AlertRuleList.vue` (webhook विकल्प और URL इनपुट जोड़ें)
- Modify: `service/plugin/ads-api/controller/v1/AlertController.php` (नियम निर्माण/अपडेट webhook_url स्वीकार करे)
- Modify: `service/plugin/ads-alert/model/AlertRule.php` (fillable/casts में webhook_url जोड़ें)
- Modify: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER या इंक्रीमेंटल स्क्रिप्ट स्पष्टीकरण)

### स्वीकृति
- [ ] email चैनल: SMTP कॉन्फ़िगर करने पर अलर्ट ट्रिगर होते ही मेल मिले; कॉन्फ़िग न होने पर सुंदर डिग्रेडेशन
- [ ] webhook चैनल: अलर्ट ट्रिगर पर कॉन्फ़िगर URL पर POST JSON, पेलोड फ़ील्ड पूर्ण
- [ ] sms चैनल: प्लेसहोल्डर रहे, लॉग दर्ज हो
- [ ] web चैनल और Redis pub/sub रिग्रेशन अप्रभावित
- [ ] Admin नियम फ़ॉर्म नए चैनल फ़ील्ड कॉन्फ़िगर कर सकता है
- [ ] `php vendor/bin/phpunit --no-coverage` सभी पास
- [ ] नए/अपडेट टेस्ट: AlertEngine/NotificationService चैनल वितरण टेस्ट
