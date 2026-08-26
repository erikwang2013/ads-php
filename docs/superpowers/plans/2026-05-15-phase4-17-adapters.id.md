# Phase 4: Rencana Ekspansi Adapter Platform Skala Besar

[中文](docs/superpowers/plans/2026-05-15-phase4-17-adapters.md) | [English](docs/superpowers/plans/2026-05-15-phase4-17-adapters.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase4-17-adapters.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase4-17-adapters.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase4-17-adapters.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase4-17-adapters.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase4-17-adapters.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase4-17-adapters.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase4-17-adapters.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase4-17-adapters.ja.md)

> Menambahkan 17 adapter platform iklan baru (7 domestik + 10 luar negeri)

## Adapter yang sudah ada (7)
巨量引擎 (Juliang)、百度营销 (Baidu Marketing)、淘宝/阿里妈妈 (Taobao/Alimama)、腾讯广告 (Tencent Ads)、友盟 (Umeng)、快手磁力引擎 (Kuaishou Magi Engine)、小红书蒲公英 (Xiaohongshu Pugongying)

## Platform domestik baru (7)

| # | Platform | Kelas Adapter | Karakteristik API |
|---|----------|---------------|-------------------|
| 17 | Weibo Fans Tong | Weibo.php | OAuth2, Bearer token, Mata uang: sen, Laporan sinkron |
| 18 | Bilibili Huahuo | Bilibili.php | OAuth2, Bearer token, Mata uang: sen, Fokus pemasaran konten |
| 19 | Iklan Youku | Youku.php | Keluarga Alibaba (tanda tangan sama seperti Taobao), Mata uang: yuan→sen |
| 20 | Iklan Meituan | Meituan.php | OAuth2, Bearer token, Mata uang: sen, Layanan lokal |
| 21 | Iklan Zhihu | Zhihu.php | OAuth2, Bearer token, Mata uang: yuan→sen, Pemasaran konten |
| 22 | Promosi 360 | Qihoo360.php | OAuth2, tanda tangan API Key, Mata uang: yuan→sen |
| 23 | Promosi Sogou | Sogou.php | OAuth2, tanda tangan API Key, Mata uang: yuan→sen |

## Platform luar negeri baru (10)

| # | Platform | Kelas Adapter | Karakteristik API |
|---|----------|---------------|-------------------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, Mata uang: sen, Token sistem |
| 25 | Amazon Ads | Amazon.php | OAuth2 (Login with Amazon), Auth berbasis Profil, Mata uang: sen |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, Mata uang: sen, REST API |
| 27 | The Trade Desk | TheTradeDesk.php | Header API Key + Secret, DSP, Mata uang: sen |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, Mata uang: sen (micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, Mata uang: sen, Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, Mata uang: sen |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, Mata uang: sen, API terbatas |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, Mata uang: sen (micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, Mata uang: sen, Tanda tangan kustom |

## Prinsip Desain

Semua adapter mengikuti antarmuka PlatformAdapter yang terpadu. Perbedaan inti hanya pada:
1. **Cara autentikasi**: OAuth2 Bearer / parameter URL / Header API Key+Sign / OAuth1.0a
2. **Satuan mata uang**: dikonversi terpadu ke sen (domestik)/sen-cent (luar negeri), perbedaan platform ditangani di dalam adapter
3. **Mode laporan**: paginasi sinkron / async buat→polling→ambil
4. **capabilities**: sebagian platform tidak mendukung manajemen campaign, hanya report

## Strategi Implementasi

Dikelompokkan berdasarkan kesamaan platform, setiap batch 4-5 adapter dibuat paralel:
- **Batch A (Domestik seri OAuth2)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (Domestik seri tanda tangan)**: Youku, Qihoo360, Sogou
- **Batch C (Internasional seri Meta)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (Internasional seri DSP)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

Setiap batch 4-5 adapter, modifikasi bootstrap.php untuk pendaftaran.
