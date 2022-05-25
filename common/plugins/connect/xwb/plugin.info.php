<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\connect\xwb;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 */

return array(
    'code' => 'xwb',
    'name' => '新浪微博登录',
    'desc' => '新浪微博登录',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'config' => array(
        'WB_AKEY' => array(
            'type' => 'text',
            'text' => 'WB_AKEY'
        ),
        'WB_SKEY' => array(
            'type' => 'text',
            'text' => 'WB_SKEY'
        )
    )
);