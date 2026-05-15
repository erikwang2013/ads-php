<?php
use plugin\ads_platform\src\AdapterRegistry;
use plugin\ads_platform\adapter\Juliang;

AdapterRegistry::register(new Juliang());
