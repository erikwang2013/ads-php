<?php
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;
use plugin\ads_platform\adapter\Baidu;
use plugin\ads_platform\adapter\Taobao;
use plugin\ads_platform\adapter\Umeng;
use plugin\ads_platform\adapter\Tencent;
use plugin\ads_platform\adapter\Kuaishou;
use plugin\ads_platform\adapter\Xiaohongshu;

AdapterRegistry::register(new Juliang());
AdapterRegistry::register(new Baidu());
AdapterRegistry::register(new Taobao());
AdapterRegistry::register(new Umeng());
AdapterRegistry::register(new Tencent());
AdapterRegistry::register(new Kuaishou());
AdapterRegistry::register(new Xiaohongshu());
