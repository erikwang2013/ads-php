# Phase 4: 大规模平台适配器扩展计划

> 新增 17 个广告平台适配器（国内 7 + 国外 10）

## 已有适配器（7个）
巨量引擎、百度营销、淘宝/阿里妈妈、腾讯广告、友盟、快手磁力引擎、小红书蒲公英

## 新增国内平台（7个）

| # | 平台 | Adapter类 | API特点 |
|---|------|-----------|---------|
| 17 | 微博粉丝通 | Weibo.php | OAuth2, Bearer token, 金额:分, 同步报表 |
| 18 | B站花火 | Bilibili.php | OAuth2, Bearer token, 金额:分, 内容营销为主 |
| 19 | 优酷广告 | Youku.php | 阿里系(同Taobao签名), 金额:元→分 |
| 20 | 美团广告 | Meituan.php | OAuth2, Bearer token, 金额:分, 本地生活 |
| 21 | 知乎广告 | Zhihu.php | OAuth2, Bearer token, 金额:元→分, 内容营销 |
| 22 | 360推广 | Qihoo360.php | OAuth2, API Key签名, 金额:元→分 |
| 23 | 搜狗推广 | Sogou.php | OAuth2, API Key签名, 金额:元→分 |

## 新增国外平台（10个）

| # | 平台 | Adapter类 | API特点 |
|---|------|-----------|---------|
| 24 | Meta Ads | Meta.php | OAuth2, Graph API, 金额:分(cent), 系统Token |
| 25 | Amazon Ads | Amazon.php | OAuth2(Login with Amazon), Profile-based auth, 金额:分(cent) |
| 26 | LinkedIn Ads | Linkedin.php | OAuth2, Bearer token, 金额:分(cent), REST API |
| 27 | The Trade Desk | TheTradeDesk.php | API Key + Secret header, DSP, 金额:分(cent) |
| 28 | Snapchat Ads | Snapchat.php | OAuth2, Bearer token, 金额:分(cent micro) |
| 29 | Spotify Ads | Spotify.php | OAuth2, Bearer token, 金额:分(cent), Audio |
| 30 | Twitch Ads | Twitch.php | OAuth2, Bearer token, 金额:分(cent) |
| 31 | Netflix Ads | Netflix.php | OAuth2 client_credentials, 金额:分(cent), 有限API |
| 32 | Pinterest Ads | Pinterest.php | OAuth2, Bearer token, 金额:分(cent micro) |
| 33 | Twitter/X Ads | Twitter.php | OAuth1.0a + OAuth2, 金额:分(cent), 自定义签名 |

## 设计原则

所有适配器遵循统一的 PlatformAdapter 接口。核心差异仅在于：
1. **认证方式**: OAuth2 Bearer / URL参数 / Header API Key+Sign / OAuth1.0a
2. **金额单位**: 统一转为分(国内)/分-cent(国外)，适配器内部处理平台差异
3. **报表模式**: 同步分页 / 异步创建→轮询→获取
4. **capabilities**: 部分平台不支持 campaign 管理，仅 report

## 实施策略

按平台共性分组，每组 4-5 个适配器并行创建：
- **Batch A (国内 OAuth2 系)**: Weibo, Bilibili, Meituan, Zhihu
- **Batch B (国内签名系)**: Youku, Qihoo360, Sogou
- **Batch C (国际 Meta系)**: Meta, LinkedIn, Snapchat, Pinterest, Twitter
- **Batch D (国际 DSP系)**: Amazon, TheTradeDesk, Spotify, Twitch, Netflix

每批 4-5 个适配器，修改 bootstrap.php 注册。
