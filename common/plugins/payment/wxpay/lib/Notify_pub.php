<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\wxpay\lib;

use common\plugins\payment\wxpay\lib\Wxpay_server_pub;

/**
 * @Id Notify_pub.php 2018.6.3 $
 * @author mosir
 *
 */
 
class Notify_pub extends Wxpay_server_pub 
{
	function __construct($config) 
	{
		parent::__construct($config);
	}
}
