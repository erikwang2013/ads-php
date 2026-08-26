# Phase 6: Erik Stack आर्किटेक्चर रीफैक्टरिंग

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> व्यापक रीफैक्टरिंग: डेटाबेस प्रीफ़िक्स, ID प्रणाली, एन्क्रिप्शन प्रणाली, कॉपीराइट, कोड मानक

## परिवर्तन सूची

| # | परिवर्तन | पैकेज | प्रभाव क्षेत्र |
|---|------|----|---------|
| 1 | डेटाबेस टेबल प्रीफ़िक्स `erik_` | — | सभी SQL/माइग्रेशन फ़ाइलें |
| 2 | प्राथमिक कुंजी Snowflake ID (कोई auto-increment नहीं) | erikwang2013/snowflake-php | सभी Model + SQL |
| 3 | API ID hashids एन्क्रिप्शन/डिक्रिप्शन | erikwang2013/hashids | सभी Controller रिस्पॉन्स |
| 4 | JWT प्रमाणीकरण स्विच | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | API संवेदनशील डेटा एन्क्रिप्शन/डिक्रिप्शन | erikwang2013/encryption | API अनुरोध/रिस्पॉन्स परत |
| 6 | DB संवेदनशील डेटा एन्क्रिप्शन/डिक्रिप्शन | erikwang2013/encryptable | Eloquent Model परत |
| 7 | ES डेटा सिंक/क्वेरी | erikwang2013/webman-scout | रिपोर्ट खोज |
| 8 | देश ध्वज | erikwang2013/season | फ़्रंटएंड प्लेटफ़ॉर्म टैग |
| 9 | कॉपीराइट घोषणा | — | सभी फ़ाइल हेडर |
| 10 | ग्लोबल `\` प्रीफ़िक्स हटाएँ | — | सभी PHP फ़ाइलें |
| 11 | कॉन्फ़िग फ़ाइलों में टिप्पणियाँ जोड़ें | — | config/*.php |
| 12 | Flutter Web PC लेआउट | — | Flutter प्रोजेक्ट |
| 13 | Admin पैनल विज़ुअलाइज़ेशन संवर्धन | — | डैशबोर्ड चार्ट |
| 14 | पैनल डेटा PDF निर्यात | — | नया निर्यात प्रारूप |
| 15 | Excel निर्यात (Client+Admin) | — | निर्यात संवर्धन |
| 16 | HarmonyOS App | — | नया हार्मनी प्रोजेक्ट |

## कार्यान्वयन क्रम

**Batch A: इन्फ्रास्ट्रक्चर (निर्भरताएँ + ID + एन्क्रिप्शन)**
- composer.json अपडेट करके 6 erikwang2013 पैकेज जोड़ें
- सभी SQL माइग्रेशन फ़ाइलें फिर से लिखें (erik_ प्रीफ़िक्स + bigint कोई auto-increment नहीं)
- Snowflake ID trait बनाएँ
- सभी Model अपडेट करें (SnowflakeTrait उपयोग करें)
- hashids मिडलवेयर कॉन्फ़िगर करें
- JWT को jwt-webman पर स्विच करें

**Batch B: कोड सफाई**
- सभी `\` ग्लोबल प्रीफ़िक्स हटाएँ
- सभी फ़ाइलों में कॉपीराइट हेडर जोड़ें
- कॉन्फ़िग फ़ाइलों में टिप्पणियाँ जोड़ें

**Batch C: फ़्रंटएंड संवर्धन**
- Admin पैनल विज़ुअलाइज़ेशन संवर्धन (अधिक चार्ट, रीयल-टाइम डेटा)
- पैनल डेटा PDF निर्यात
- Excel निर्यात संवर्धन

**Batch D: Flutter + HarmonyOS**
- Flutter Web PC लेआउट प्रोजेक्ट
- HarmonyOS प्रोजेक्ट स्केलेटन
