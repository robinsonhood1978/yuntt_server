<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\tenpay_wap;

use yii;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.7.24 $
 * @author mosir
 */

return array(
    'code'      => 'tenpay_wap',
    'name'      => Language::get('tenpay_wap'),
    'subname'   => Language::get('tenpay'),
    'desc'      => Language::get('tenpay_desc'),
    'is_online' => '1',
    'author'    => 'SHOPWIND',
    'website'   => 'https://www.shopwind.net',
    'version'   => '1.0',
    'currency'  => Language::get('tenpay_currency'),
    'config'    => array(
        'partner'   => array(        //账号
            'text'  => Language::get('partner'),
            'desc'  => Language::get('partner_desc'),
            'type'  => 'text',
        ),
        'key'       => array(        //密钥
            'text'  => Language::get('key'),
            'type'  => 'text',
        ),
    ),
);