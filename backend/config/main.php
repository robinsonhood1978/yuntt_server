<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
			'enableCookieValidation' => true,
			'cookieValidationKey' => 'abcdefg1234567890',
        ],
        'user' => file_exists(Yii::getAlias('@frontend') . '/web/data/install.lock') ? [
            'identityClass' => 'common\models\UserModel',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
			'loginUrl' => ['user/login']
        ] : null,
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
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
            'errorAction' => 'default/error'
        ],
		'view' => [
            'theme' => [
                'basePath' => '@app/views/default',
                'baseUrl' => '@web/views/default',
                'pathMap' => [
					'@app/views' => [
        				'@app/views/default',
    				],
    				'@app/modules' => '@app/views/default/modules',
    				'@app/widgets' => '@app/views/default/widgets'
                ],
            ],
        ],
    ],
    'params' => $params,
];
