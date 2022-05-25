<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\alipay;

use yii;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 *
 */

return array(
    'code'      => 'alipay',
    'name'      => Language::get('alipay'),
    'subname'   => Language::get('alipay'),
    'desc'      => Language::get('alipay_desc'),
    'is_online' => '1',
    'author'    => 'SHOPWIND',
    'website'   => 'https://www.shopwind.net',
    'version'   => '1.0',
    'currency'  => Language::get('alipay_currency'),
    'config'    => array(
        'appId'   => array(        //APPID
            'text'  => Language::get('appId'),
            'desc'  => Language::get('appId_desc'),
            'type'  => 'text',
        ),
		'rsaPublicKey'       => array(        // 应用公钥
            'text'  => Language::get('rsaPublicKey'),
            'desc'  => Language::get('rsaPublicKey_desc'),
            'type'  => 'text',
        ),
        'rsaPrivateKey'       => array(        // 应用私钥
            'text'  => Language::get('rsaPrivateKey'),
            'desc'  => Language::get('rsaPrivateKey_desc'),
            'type'  => 'text',
        ),
        'alipayrsaPublicKey'   => array(        // 支付宝公钥
            'text'  => Language::get('alipayrsaPublicKey'),
			'desc'  => Language::get('alipayrsaPublicKey_desc'),
            'type'  => 'text',
        ),
		'signType'  => array(         // 签名类型
            'text'      => Language::get('signType'),
            'type'      => 'select',
            'items'     => array(
                'RSA2'   => Language::get('signType_RSA2'),
				'RSA'    => Language::get('signType_RSA'),
            ),
        ),
    ),
);