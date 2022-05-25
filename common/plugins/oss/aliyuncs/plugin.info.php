<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\oss\aliyuncs;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'aliyuncs',
    'name' => '阿里云OSS',
    'desc' => '阿里云OSS对象存储',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'config' => array(
		'accessKeyId' => array(
            'type' => 'text',
            'text' => 'accessKeyId'
        ),
		'accessKeySecret' => array(
            'type' => 'text',
            'text' => 'accessKeySecret'
        ),
        'bucket' => array(
            'type' => 'text',
            'text' => 'bucket'
        ),
        //'lanDomain' => array(
            //'type' => 'text',
           // 'text' => 'lanDomain'
        //),
        'wanDomain' => array(
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