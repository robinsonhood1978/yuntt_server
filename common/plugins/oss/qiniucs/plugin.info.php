<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\oss\qiniucs;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'qiniucs',
    'name' => '七牛云OSS',
    'desc' => '七牛云OSS对象存储',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'config' => array(
		'accessKey' => array(
            'type' => 'text',
            'text' => 'accessKey'
        ),
		'secretKey' => array(
            'type' => 'text',
            'text' => 'secretKey'
        ),
        'bucket' => array(
            'type' => 'text',
            'text' => 'bucket'
        ),
        'domain' => array(
            'type' => 'text',
            'text' => 'Endpoint（区域节点）'
        ),
        'ossUrl' => array(
            'type' => 'text',
            'text' => '空间域名',
            'placeholder' => 'https://'
        ),
    )
);