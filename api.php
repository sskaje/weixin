<?php
/**
 * 微信接口文件定义
 *
 * 访问方式: http://wx.sskaje.me/api.php?app=example
 *
 * @author sskaje
 */

require(__DIR__ . '/classes/weixin.inc.php');

spWxError::SetExceptionHandler();

if (!isset($_GET['app'])) {
    throw new Exception('Bad app', 10001);
}

$app_name = strtolower($_GET['app']);
if (!preg_match('#^[a-z0-9]+$#i', $app_name)) {
    throw new Exception('Bad app', 10002);
}

$app_file = __DIR__ . '/app/' . $app_name . '.php';
if (!is_file($app_file)) {
    throw new Exception('Application file not found', 10003);
}

require($app_file);

foreach ($GLOBALS['REQUEST_HANDLERS'] as $request_type=>$handler_class) {
    if (!class_exists($handler_class) || !is_subclass_of($handler_class, 'spWxRequest')) {
        throw new Exception('Bad request handler ' . $handler_class, 10004);
    }

    spWxMessage::RegisterHandler($request_type, $handler_class);
}

spWeixin::Api();

exit;
