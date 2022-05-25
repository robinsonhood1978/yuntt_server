<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\fullprefer;

use yii;
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'fullprefer',
    'name' => '满优惠',
    'desc' => '购满一定金额直接减价或折扣优惠',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'category' => 'store', // user/store
    'icon' => 'icon-zhifu1',
    'buttons' => array(
        array(
            'label' => Language::get('setting'),
            'url' => Url::toRoute(['seller_fullprefer/index'], Basewind::homeUrl()),
            'dialog' => true
        )
    )
);