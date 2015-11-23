<?php

define('SPWX_API_TOKEN',  '');
define('SPWX_APP_ID',     '');
define('SPWX_APP_SECRET', '');
define('SPWX_APP_AESKEY', '');

define('SPWX_REDIS_HOST', '127.0.0.1');
define('SPWX_REDIS_PORT', 6379);

define('SPWX_APP_NAME', '');

define('SPWX_MENU_CLASS', '');

$GLOBALS['REQUEST_HANDLERS'] = [
    spWxMessage::REQUEST_TEXT     => 'CMRequestDefault',
    spWxMessage::REQUEST_URL      => 'CMRequestDefault',
    spWxMessage::REQUEST_LOCATION => 'CMRequestDefault',
    spWxMessage::REQUEST_IMAGE    => 'CMRequestDefault',
    spWxMessage::REQUEST_VOICE    => 'CMRequestDefault',
    spWxMessage::REQUEST_VIDEO    => 'CMRequestDefault',

    spWxMessage::REQUEST_EVENT    => 'CMRequestEventHandler',
];
