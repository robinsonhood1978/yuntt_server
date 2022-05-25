<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\meal;

use yii;

use common\library\Language;
use common\plugins\BasePromote;

/**
 * @Id meal.plugin.php 2018.6.5 $
 * @author mosir
 */

class Meal extends BasePromote
{
	/**
     * 插件实例
	 * @var string $code
	 */
    protected $code = 'meal';

    /**
	 * 构造函数
	 */
	public function __construct()
	{
        parent::__construct();
    }
}

