<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\cashcard;

use yii;
use yii\helpers\Url;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'cashcard',
    'name' => '充值卡',
    'desc' => '结合线下派发充值卡，激发用户入店消费',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'category' => 'user', // user/store
    'icon' => 'icon-chongzhika',
    'buttons' => array(
        array(
            'label' => Language::get('manage'),
            'url' => Url::toRoute(['cashcard/index'])
        )
    ),
);