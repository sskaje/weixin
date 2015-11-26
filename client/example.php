<?php

require(__DIR__ . '/client.php');

/**
 * App.php访问需要的密钥
 */
define('SPWX_AUTH_KEY', '');
/**
 * App Name
 */
define('SPWX_APP_NAME', 'example');
/**
 * API URL
 */
define('SPWX_API_URL', 'http://wx.sskaje.me/app.php');


$client = new spWxClient(SPWX_APP_NAME, SPWX_AUTH_KEY, SPWX_API_URL);

$ret = $client->app('user_info', ['']);

var_dump($ret);