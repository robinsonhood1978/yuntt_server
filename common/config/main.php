<?php

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
	'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
		'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'as log' => \yii\queue\LogBehavior::class,//错误日志 默认为 console/runtime/logs/app.log
            'path' => '@console/runtime/queue',// 不要设置路径为：@runtime/queue
		],
		'cache' => [
       		'class' => 'yii\caching\FileCache',
			//'cachePath' => '@runtime/cache', // 不要设置该参数
		],
		'redis' => [
			'class' => 'yii\redis\Connection',
			'hostname' => 'localhost',
			'port' => 6379,
			'database' => 0,
		],
		'assetManager' => [
			'bundles' => false,
			// 注意！！！开启后可能会误删源文件（即：删除临时文件，也同时删除源文件，造成重大损失！！！）
			'linkAssets' => false, 
			//'forceCopy' => true, // 开启后显著降低性能
			//'appendTimestamp' => true,// file add version， as： yii.js?v=1423448645
     	],
		'authManager' => [
            'class' => 'yii\rbac\PhpManager',
			'itemFile' => '@common/rbac/items.php',
			'assignmentFile' => '@common/rbac/assignments.php',
			'ruleFile' => '@common/rbac/rules.php',
        ],
		'cart' => [
			'class' => 'common\components\cart\Cart',
			'storageClass' => 'common\components\cart\storage\DbSessionStorage',
			'params' => [
				'key' => 'cart',
				'expire' => 604800,
			]
		],
		// url route
		'urlManager' => [
			// Disable r= routes
            'enablePrettyUrl' => true,
			// Disable index.php
            'showScriptName' => false,
			'rules'=>[
				'index' 							=> 'default/index',
				'login'								=> 'user/login',
				'register'							=> 'user/register',
				'logout'							=> 'user/logout',
				'search/goods'						=> 'search/index',
				'goods'								=> 'goods/index',
       			'<controller:\w+>/<action:\w+>'		=> '<controller>/<action>',
       		],
			'suffix'         						=> '.html',
        ],
		// view config
		'view' => [
			'renderers' => [
                'html' => [ // view file prefix
                    'class' => 'yii\smarty\ViewRenderer',
                    //'cachePath' => '@runtime/Smarty/cache',
					'options' => [
						'error_reporting' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT'
                        //'left_delimiter' => '{',
                       // 'right_delimiter' => '}',
                    ],
                ],
            ],
        ],
		'i18n' => [
            'translations' => [
               '*' => [
                  'class' => 'yii\i18n\PhpMessageSource',
				  'basePath' => '@app/languages',
                  //'fileMap' => [
                     //'controller' => 'default.php'
                  //],
               ],
            ],
		 ]
	],
	// set default route
	'defaultRoute' => 'default',
	// set target language to be chinese
	'language' => 'zh-CN',
    // set source language to be English
	'sourceLanguage' => 'en-US',
];
