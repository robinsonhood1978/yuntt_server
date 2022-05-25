<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\express\kuaidi100;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 */

return array(
    'code' => 'kuaidi100',
    'name' => '快递100',
    'desc' => '快递100物流跟踪插件',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'config' => array(
		'key' => array(
            'type' => 'text',
            'text' => 'key'
        ),
		'customer' => array(
            'type' => 'text',
            'text' => 'customer'
        ),
    )
);