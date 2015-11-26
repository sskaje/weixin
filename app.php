<?php
/**
 * App接口
 *
 * 访问方式: http://wx.sskaje.me/app.php?app=example&ts=1401010101&password=1234567890123456
 *
 * @author sskaje
 */
require(__DIR__ . '/classes/weixin.inc.php');

spWxError::SetExceptionHandler();

if (!isset($_GET['app']) || !isset($_GET['ts']) || !isset($_GET['password'])) {
    throw new spWxException('缺少参数', 20001);
}

$request_ts = $_SERVER['REQUEST_TIME'];
if (abs($request_ts - $_GET['ts']) > 10) {
    throw new spWxException('TS无效' . $_GET['ts'] . ':' . $request_ts, 20002);
}

if (defined('SPWX_AUTH_KEY') && SPWX_AUTH_KEY !== '') {
    if ($_GET['password'] != md5('sskaje' . SPWX_AUTH_KEY . $_GET['app'] . $_GET['ts'])) {
        throw new spWxException('密码错误', 20003);
    }
}

$app_name = strtolower($_GET['app']);
if (!preg_match('#^[a-z0-9]+$#i', $app_name)) {
    throw new spWxException('Bad app', 20004);
}

$app_file = __DIR__ . '/app/' . $app_name . '.php';
if (!is_file($app_file)) {
    throw new spWxException('Application file not found', 20005);
}

require($app_file);


$available_ops = [
    'oauth',
    'app',
];
if (!isset($_GET['op']) || !in_array($_GET['op'], $available_ops)) {
    throw new spWxException('OP 无效', 20006);
}
if (!isset($_GET['action'])) {
    throw new spWxException('A 无效', 20007);
}

$op = $_GET['op'];

if ($op == 'oauth') {
    $obj = spWeixin::OAuth();
} else if ($op == 'app') {
    $obj = spWeixin::App();
} else {
    throw new spWxException('错误的op', 20008);
}

$action = $_GET['action'];
if (!is_callable([$obj, $action])) {
    throw new spWxException('A 无效', 20009);
}

$params = isset($_POST['params']) ? (array) json_decode($_POST['params'], true) : [];
$params = array_values($params);

$result = call_user_func_array([$obj, $action], $params);

echo json_encode([
    'code'  =>  0,
    'data'  =>  $result,
], JSON_UNESCAPED_UNICODE);
exit;