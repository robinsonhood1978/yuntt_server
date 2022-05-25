<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\unionpay;

use yii;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 */

return array(
    'code'      => 'unionpay',
    'name'      => Language::get('unionpay'),
    'subname'   => Language::get('unionpay'),
    'desc'      => Language::get('unionpay_desc'),
    'is_online' => '1',
    'author'    => 'SHOPWIND',
    'website'   => 'https://www.shopwind.net',
    'version'   => '1.0',
    'currency'  => Language::get('unionpay_currency'),
    'config'    => array(
        'merId'   => array(
            'text'  => Language::get('merId'),
            'desc'  => Language::get('merId_desc'),
            'type'  => 'text',
        ), 
    )
);