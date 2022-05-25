<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
			'enableCookieValidation' => true,
			'cookieValidationKey' => 'abcdefg1234567890999',
        ],
        'user' => file_exists(Yii::getAlias('@frontend') . '/web/data/install.lock') ? [
            'identityClass' => 'common\models\UserModel',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
			'loginUrl' => ['user/login']
        ] : null,
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
					'logFile' => '@runtime/logs/app-'.date('Y-m-d', time()).'.log',
					'maxFileSize' => 512
                ]
            ]
        ],
        'errorHandler' => [
            'errorAction' => 'site/error'
        ],
		'view' => [
            'theme' => [
                'basePath' => '@app/views/default',
                'baseUrl' => '@web/views/default',
                'pathMap' => [
					'@app/views' => [
        				'@app/views/default'
    				]
                ]
            ]
        ]
    ],
    'params' => $params
];
