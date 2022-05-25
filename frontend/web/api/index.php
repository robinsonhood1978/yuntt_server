<?php
/**
 * 解决H5端跨域问题
 * 如果需要配置H5端，请把下面注释去掉，然后把域名改为自己的，请务必填写域名，请不要使用通配符（*）以免引发安全问题
 */
header("Access-Control-Allow-Origin:https://h5.shopwind.net");
header("Access-Control-Allow-Headers:Content-Type");
header("Access-Control-Allow-Methods:POST");
header("Access-Control-Allow-Credentials: false");

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../../common/config/bootstrap.php';
require __DIR__ . '/../../../apiserver/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../../common/config/main.php',
    require __DIR__ . '/../../../common/config/main-local.php',
    require __DIR__ . '/../../../apiserver/config/main.php',
    require __DIR__ . '/../../../apiserver/config/main-local.php'
);

(new yii\web\Application($config))->run();
