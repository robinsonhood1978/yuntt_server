<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\teambuy;

use yii;
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'teambuy',
    'name' => '拼团',
    'desc' => '设置单品为邀请成团后以拼团价购买',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'category' => 'store', // user/store
    'icon' => 'icon-pintuan2',
    'buttons' => array(
        array(
            'label' => Language::get('setting'),
            'url' => Url::toRoute(['teambuy/index'], Basewind::homeUrl()),
            'dialog' => true
        )
    )
);