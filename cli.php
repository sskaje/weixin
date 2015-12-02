<?php
/**
 * 命令行微信工具接口
 * 直接不带参数执行可以看帮助
 *
 * @author sskaje
 */
require(__DIR__ . '/classes/weixin.inc.php');
# 启用Exception handler
spWxError::SetExceptionHandler();

if (!isset($argv[2])) {
    usage();
}

$app_name = strtolower($argv[1]);
if (!preg_match('#^[a-z0-9]+$#i', $app_name)) {
    usage();
}

$app_file = __DIR__ . '/app/' . $app_name . '.php';
if (!is_file($app_file)) {
    throw new spWxException('微信应用文件不存在', 30001);
}

require($app_file);

$command = strtolower($argv[2]);

$app = spWeixin::App();


if ($command == 'menu_create') {
    $menu_class = SPWX_MENU_CLASS;

    $ret = $app->menu_create(new $menu_class());
    var_dump($ret);
} else if ($command == 'menu_get') {
    $ret = $app->menu_get();
    var_dump($ret);
} else if ($command == 'menu_delete') {
    $ret = $app->menu_delete();
    var_dump($ret);
}




function usage($err = '')
{
    $err = trim($err);
    if ($err) {
        echo <<<ERROR
Error:
    {$err}

ERROR;

    }

    echo <<<USAGE

Weixin App CLI tools

Author: sskaje

Usage:
    php cli.php APP_NAME COMMAND OPTIONS

        App Name:               ^[A-Za-z0-9]+\$
        Commands:
            menu_create         create menu
            menu_get            get menu json
            menu_delete         delete menu



USAGE;

    exit;
}

# EOF