<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\sms\smschinese;

use yii;
use yii\helpers\Url;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'smschinese',
    'name' => '网建短信通',
    'desc' => '中国网建SMS短信通',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'buttons' => array(
        array(
            'label' => Language::get('manage'),
            'url' => Url::toRoute(['msg/index'])
        )
    ),
    'config' => array(
		'uid' => array(
            'type' => 'text',
            'text' => '用户名'
        ),
		'key' => array(
            'type' => 'text',
            'text' => '短信密钥'
        ),
        'scene' => array(
            'name' => 'config[scene]',
            'type' => 'checkbox',
            'text' => '启用场景',
            'items' => array(
                'register' => '用户注册',
                'find_password' => '找回密码'
            )
        )
    )
);