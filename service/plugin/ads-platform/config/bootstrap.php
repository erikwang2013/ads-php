<?php
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;
use plugin\ads_platform\adapter\Baidu;

AdapterRegistry::register(new Juliang());
AdapterRegistry::register(new Baidu());
