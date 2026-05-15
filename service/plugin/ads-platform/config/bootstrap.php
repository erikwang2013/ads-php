<?php
/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;
use plugin\ads_platform\adapter\Baidu;
use plugin\ads_platform\adapter\Taobao;
use plugin\ads_platform\adapter\Umeng;
use plugin\ads_platform\adapter\Tencent;
use plugin\ads_platform\adapter\Kuaishou;
use plugin\ads_platform\adapter\Xiaohongshu;
use plugin\ads_platform\adapter\Youku;
use plugin\ads_platform\adapter\Qihoo360;
use plugin\ads_platform\adapter\Sogou;
use plugin\ads_platform\adapter\Weibo;
use plugin\ads_platform\adapter\Bilibili;
use plugin\ads_platform\adapter\Meituan;
use plugin\ads_platform\adapter\Zhihu;
use plugin\ads_platform\adapter\Amazon;
use plugin\ads_platform\adapter\TheTradeDesk;
use plugin\ads_platform\adapter\Spotify;
use plugin\ads_platform\adapter\Twitch;
use plugin\ads_platform\adapter\Netflix;
use plugin\ads_platform\adapter\Meta;
use plugin\ads_platform\adapter\Linkedin;
use plugin\ads_platform\adapter\Snapchat;
use plugin\ads_platform\adapter\Pinterest;
use plugin\ads_platform\adapter\Tiktok;
use plugin\ads_platform\adapter\Twitter;
use plugin\ads_platform\adapter\Youtube;
use plugin\ads_platform\adapter\Jingdong;
use plugin\ads_platform\adapter\Pinduoduo;
use plugin\ads_platform\adapter\Google;

AdapterRegistry::register(new Juliang());
AdapterRegistry::register(new Baidu());
AdapterRegistry::register(new Taobao());
AdapterRegistry::register(new Umeng());
AdapterRegistry::register(new Tencent());
AdapterRegistry::register(new Kuaishou());
AdapterRegistry::register(new Xiaohongshu());
AdapterRegistry::register(new Youku());
AdapterRegistry::register(new Youtube());
AdapterRegistry::register(new Qihoo360());
AdapterRegistry::register(new Sogou());
AdapterRegistry::register(new Weibo());
AdapterRegistry::register(new Bilibili());
AdapterRegistry::register(new Meituan());
AdapterRegistry::register(new Zhihu());
AdapterRegistry::register(new Amazon());
AdapterRegistry::register(new TheTradeDesk());
AdapterRegistry::register(new Tiktok());
AdapterRegistry::register(new Spotify());
AdapterRegistry::register(new Twitch());
AdapterRegistry::register(new Netflix());
AdapterRegistry::register(new Meta());
AdapterRegistry::register(new Linkedin());
AdapterRegistry::register(new Snapchat());
AdapterRegistry::register(new Pinterest());
AdapterRegistry::register(new Twitter());
AdapterRegistry::register(new Jingdong());
AdapterRegistry::register(new Pinduoduo());
AdapterRegistry::register(new Google());
