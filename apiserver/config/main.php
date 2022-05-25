<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-apiserver',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'apiserver\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-apiserver',
			'enableCookieValidation' => true,
			'cookieValidationKey' => 'abcdefg1234567890999',
			// config this can receive post or json data [Yii::$app->request->post()]
			'parsers' => [
				'application/json' => 'yii\web\JsonParser',
				'text/json' => 'yii\web\JsonParser',
			]
        ],
        'user' => file_exists(Yii::getAlias('@frontend') . '/web/data/install.lock') ? [
            'identityClass' => 'common\models\UserModel',
            'enableAutoLogin' => false,
            'identityCookie' => ['name' => '_identity-apiserver', 'httpOnly' => true],
			'loginUrl' => null,//['user/login']
			'enableSession' => false
        ] : null,
        'session' => [
            // this is the name of the session cookie used for login on the apiserver
            'name' => 'advanced-apiserver',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
					'logFile' => '@runtime/logs/app-'.date('Y-m-d', time()).'.log',
					'maxFileSize' => 512
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error'
        ],
		// url route
		'urlManager' => [
			// Disable r= routes
            'enablePrettyUrl' => true,
			// Disable index.php
            'showScriptName' => false,
			'rules'=>[
				//'index' 							=> 'default/index',
       			'<controller:\w+>/<action:\w+>'		=> '<controller>/<action>',
       		],
			'suffix'         						=> '',
        ],
		'view' => [],
    ],
    'params' => $params,
];
