<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;

use common\install\mysql\Mysql5;
use common\install\mysql\Mysqli7;

/**
 * @Id Install.php 2018.5.31 $
 * @author mosir
 */
 
class Install
{
	/**
	 * 数据库实例, as: mysql|mssql
	 * @var string $instance
	 */
	public $instance = null;

	/**
	 * 静态模型
	 */
	public static $_model = null;
	
	/**
	 * 构造函数
	 * @param string $instance
	 */
	public function __construct($instance = null)
	{
		if($instance !== null) {
			$this->instance = $instance;
		}
	}
	
	/**
	 * @param string $instance
	 */
	public static function getInstance($instance = null) {
		return new Install($instance);
	}
	
	/**
	 * 获取数据库实例类
	 * @var array $options
	 */
	public function build($options = null)
	{
		// 避免重复new
		if(self::$_model != null) {
			return self::$_model;
		}
		
		if($this->instance == 'mysql' || $this->instance == null || $this->instance == '')
		{
			// PHP >= 7
			if(!extension_loaded('mysql') && extension_loaded('mysqli')) {
				self::$_model = new Mysqli7($options);
			}
			else {
				self::$_model = new Mysql5($options);
			}
		}
		return self::$_model;
	}
}