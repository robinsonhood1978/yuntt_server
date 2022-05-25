<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\wxnativepay;

use yii;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 */

return array(
    'code'      => 'wxnativepay',
    'name'      => Language::get('wxnativepay'),
    'subname'   => Language::get('wxpay'),
    'desc'      => Language::get('wxnativepay_desc'),
    'is_online' => '1',
    'author'    => 'SHOPWIND',
    'website'   => 'https://www.shopwind.net',
    'version'   => '1.0',
    'currency'  => Language::get('wxpay_currency'),
    'config'    => array(
        'AppID'   => array(        // 公众号开发者应用ID
            'text'  => Language::get('appid'),
            'desc'  => Language::get('appid_desc'),
            'type'  => 'text',
        ),
		'AppSecret' => array(         // 公众号开发者应用密钥
            'text'  => Language::get('appsecret'),
            'desc'  => Language::get('appsecret_desc'),
            'type'  => 'text',
        ),
        'MchID'     => array(        //商户号
            'text'  => Language::get('mchid'),
            'desc'  => Language::get('mchid_desc'),
            'type'  => 'text',
        ),
        'KEY'   => array(        //商户密钥
            'text'  => Language::get('key'),
			'desc'  => Language::get('key_desc'),
            'type'  => 'text',
        ),
        
    ),
);