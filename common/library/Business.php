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
use yii\helpers\ArrayHelper;

/**
 * @Id Business.php 2018.3.9 $
 * @author mosir
 */
 
class Business
{
	/**
	 * 业务实体
	 * @var string $instance
	 */
	public $instance = 'order';

	/**
	 * 业务目录
	 */
	protected $basePath = '';
	
	/**
	 * 构造函数
	 */
	public function __construct($options = null)
	{
		if($options !== null) {
			if(is_string($options)) {
				$options = ['instance' => $options];
			}
			foreach($options as $key => $value) {
				$this->$key = $value;
			}
		}

		$this->basePath = Yii::getAlias('@common') . '/business';
	}
	
	public static function getInstance($options = null) {
		return new Business($options);
	}
	
	/**
	 * 创建业务实例类
	 */
	public function build($code, $post = null, $params = array())
	{
		$folder = $this->instance . 'types';
		$code_file = $this->basePath . '/' . $folder .'/' . ucfirst($code) . ucfirst($this->instance) . '.php';
		if(!is_file($code_file)) {
			return false;
		}
		include_once($code_file);

		$class_name = sprintf("common\business\%s\%s", $folder, ucfirst($code) . ucfirst($this->instance));
		return new $class_name($code, $post, $params);
	}
}