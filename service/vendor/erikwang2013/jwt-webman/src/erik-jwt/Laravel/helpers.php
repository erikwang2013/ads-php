<?php
/*
 * JWT Webman Plugin - JWT authentication for webman framework
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

if (!function_exists('jwt')) {
    function jwt(): \ErikJwt\JWT
    {
        return app('erik.jwt');
    }
}
