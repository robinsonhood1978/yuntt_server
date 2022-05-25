<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\cod;

use yii;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.7.24 $
 * @author mosir
 */

return array(
    'code'      => 'cod',
    'name'      => Language::get('cod'),
    'subname'   => Language::get('cod'),
    'desc'      => Language::get('cod_desc'),
    'is_online' => '0',
    'author'    => 'SHOPWIND',
    'website'   => 'https://www.shopwind.net',
    'version'   => '1.0',
    'currency'  => Language::get('cod_currency'),
    'config'    => array(),
);