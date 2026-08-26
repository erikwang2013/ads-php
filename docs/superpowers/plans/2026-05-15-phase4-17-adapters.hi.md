# Phase 4: बड़े पैमाने पर प्लेटफ़ॉर्म एडाप्टर विस्तार योजना

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> 17 नए विज्ञापन प्लेटफ़ॉर्म एडाप्टर जोड़ें (घरेलू 7 + विदेशी 10)

## मौजूदा एडाप्टर (7)
巨量引擎 (Juliang)、百度营销 (Baidu)、淘宝/阿里妈妈 (Taobao/Alimama)、腾讯广告 (Tencent)、友盟 (Umeng)、快手磁力引擎 (Kuaishou)、小红书蒲公英 (Xiaohongshu)

## नए घरेलू प्लेटफ़ॉर्म (7)

| # | प्लेटफ़ॉर्म | Adapter वर्ग | API विशेषताएँ |
|---|------|-----------|---------|
| 17 | 微博粉丝通 (Weibo) | Weibo.php | OAuth2, Bearer token, मुद्रा:फ़ेन, सिंक्रोनस रिपोर्ट |
| 18 | B站花火 (Bilibili) | Bilibili.php | OAuth2, Bearer token, मुद्रा:फ़ेन, मुख्यतः कंटेंट मार्केटिंग |
| 19 | 优酷广告 (Youku) | Youku.php | अली परिवार (Taobao जैसा सिग्नेचर), मुद्रा:युआन→फ़ेन |
| 20 | 美团广告 (Meituan) | Meituan.php | OAuth2, Bearer token, मुद्रा:फ़ेन, लोकल लाइफ़ |
| 21 | 知乎广告 (Zhihu) | Zhihu.php | OAuth2, Bearer token, मुद्रा:युआन→फ़ेन, कंटेंट मार्केटिंग |
| 22 | 360推广 (Qihoo360) | Qihoo360.php | OAuth2, API Key सिग्नेचर, मुद्रा:युआन→फ़ेन |
| 23 | 搜狗推广 (Sogou) | Sogou.php | OAuth2, API Key सिग्नेचर, मुद्रा:युआन→फ़ेन |

## नए विदेशी प्लेटफ़ॉर्म (10)

| # | प्लेटफ़ॉर्म | Adapter वर्ग | API विशेषताएँ |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, मुद्रा:फ़ेन (cent), सिस्टम टोकन |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), Profile-based auth, मुद्रा:फ़ेन (cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, मुद्रा:फ़ेन (cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, मुद्रा:फ़ेन (cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, मुद्रा:फ़ेन (cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, मुद्रा:फ़ेन (cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, मुद्रा:फ़ेन (cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, मुद्रा:फ़ेन (cent), सीमित API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, मुद्रा:फ़ेन (cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, मुद्रा:फ़ेन (cent), कस्टम सिग्नेचर |

## डिज़ाइन सिद्धांत

सभी एडाप्टर एकीकृत PlatformAdapter इंटरफ़ेस का पालन करते हैं। मुख्य अंतर केवल:
1. **प्रमाणीकरण विधि**: OAuth2 Bearer / URL पैरामीटर / Header API Key+Sign / OAuth1.0a
2. **मुद्रा इकाई**: एकीकृत रूप से फ़ेन (घरेलू)/फ़ेन-cent (विदेशी) में बदलें, प्लेटफ़ॉर्म अंतर एडाप्टर के अंदर संभाला जाता है
3. **रिपोर्ट मोड**: सिंक्रोनस पेजिनेशन / असिंक्रोनस बनाएँ→पोल→प्राप्त करें
4. **capabilities**: कुछ प्लेटफ़ॉर्म campaign प्रबंधन समर्थित नहीं, केवल report

## कार्यान्वयन रणनीति

प्लेटफ़ॉर्म समानता के अनुसार समूह बनाएँ, हर समूह में 4-5 एडाप्टर समानांतर बनाएँ:
- **Batch A (घरेलू OAuth2 श्रृंखला)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (घरेलू सिग्नेचर श्रृंखला)**: Youku, Qihoo360, Sogou
- **Batch C (अंतर्राष्ट्रीय Meta श्रृंखला)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (अंतर्राष्ट्रीय DSP श्रृंखला)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

हर बैच में 4-5 एडाप्टर, bootstrap.php में पंजीकरण संशोधित करें।
