<?php
require(__DIR__ . '/config/error.php');
require(__DIR__ . '/classes/weixin.class.php');
require(__DIR__ . '/classes/request.class.php');
require(__DIR__ . '/classes/response.class.php');

require(__DIR__ . '/config/app_config.php');

$app_file = __DIR__ . '/app/' . SPWX_APP_NAME . '.php';
if (!is_file($app_file)) {
    throw new SPException('微信应用文件不存在', 1001);
}

require($app_file);


function usage()
{
    echo <<<USAGE
Usage:
    php app.php COMMAND OPTIONS

        Commands:
            create_menu         CreateMenu



USAGE;

    exit;
}

if (!isset($argv[1])) {
    usage();
}

$command = strtolower($argv[1]);

$app = spWeixin::App();

if ($command == 'create_menu') {
    $menu_class = SPWX_MENU_CLASS;

    $app->createMenu($menu_class);
}
