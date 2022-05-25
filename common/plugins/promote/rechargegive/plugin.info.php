<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\rechargegive;

use yii;
use yii\helpers\Url;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'rechargegive',
    'name' => '充值返钱',
    'desc' => '用户充值，平台给予一定比率的金额用于消费',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'category' => 'user', // user/store
    'icon' => 'icon-yue',
    'buttons' => array(
        array(
            'label' => Language::get('setting'),
            'url' => Url::toRoute(['deposit/setting'])
        )
    ),
);