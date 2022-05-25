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
use yii\helpers\FileHelper;

use common\models\PluginModel;

/**
 * @Id Plugin.php 2018.5.31 $
 * @author mosir
 */
 
class Plugin
{
	/**
	 * 插件系列
	 * 比如第三方登录/OSS/支付插件/物流跟踪插件等插件系列
	 * @var string $instance
	 */
	public $instance = null;
	
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
	 * 获取插件类
	 * @param string $instance
	 */
	public static function getInstance($instance) {
		return new Plugin($instance);
	}
	
	/**
	 * 获取插件基类或者插件实例类
	 * @var string $code
	 */
	public function build($code = null, $params = null)
	{
		$base_file = Yii::getAlias('@common') . '/plugins/Base' . ucfirst($this->instance) . '.php';
		if(!is_file($base_file)) {
			return false;
		}

		if(!$code) 
		{
			include_once($base_file);

			// 插件基类
			$base_class = sprintf("common\plugins\Base%s", ucfirst($this->instance));
			return new $base_class($params);
		}

		if(($plugin_file = self::isExisted($this->instance, $code)) === false) {
			return false;
		}
		include_once($plugin_file);
		
		// 插件实例类
		$plugin_class = sprintf("common\plugins\%s\%s\%s", $this->instance, $code, ucfirst($code));

		//  创建插件实例类
		return new $plugin_class($params);
	}

	/** 
	 * 让系统来自动创建可用的插件（哪个能用就用哪个的原则）
	 * @desc 当有多个插件实例的情况下，特别是针对平台只需开启一个插件的情况下尤其方便，无需指定实例而是由系统自动获取
	 * @desc 比如OSS插件，有可能系统已经集成了阿里云OSS插件，也集成了七牛云OSS插件
	 * @desc 那么，对平台来说,只开启一个插件就行，即：要么使用阿里云OSS，要么使用七牛云OSS来上传文件
	 * @desc 通过此方法让系统来自动获取。这样，我们在后台就可以随意切换OSS上传插件
	 * @param bool $force 当后台没有配置插件时，也强制创建实例，这个只能是针对无须配置的插件
	 */
	public function autoBuild($force = false)
	{
		// 优先选启用的，如果没有启用的，也选择默认一个，避免空对象报错
		$query = PluginModel::find()->select('code,enabled')->where(['instance' => $this->instance])->orderBy(['enabled' => SORT_DESC])->one();
		if($query) {
			if($query->enabled || (!$query->enabled && $force)) {
				return self::getInstance($this->instance)->build($query->code);
			}
			return false;
		}
		
		// 如果还是没有，则说明有可能后台没有配置，有些插件，比如编辑器，上传组件确实不需要配置
		// 所以还是为了避免不必要的空对象报错，我们继续默认取一个插件实例
		if($force) {
			$dir = Yii::getAlias('@common') . '/plugins/' . $this->instance;
			if(($list = FileHelper::findDirectories($dir, ['recursive' => false]))) {
				$code = substr($list[0], strripos($list[0], DIRECTORY_SEPARATOR) + 1);
				return self::getInstance($this->instance)->build($code);
			}
		}

		return false;	
	}

	/**
	 * 判断插件目录是否存在
	 * @desc 如果传参$code，则返回插件文件
	 * @desc 如果不传，只做验证插件目录是否存在
	 * @return string|bool
	 */
	public static function isExisted($instance, $code = null)
	{
		$dir = Yii::getAlias('@common') . '/plugins/' . $instance;
		if(!is_dir($dir)) {
			return false;
		}
		if($code) {
			$plugin_file = Yii::getAlias('@common') . '/plugins/' . $instance . '/' . $code . '/' . $code . '.plugin.php';
		
			if(!is_file($plugin_file)) {
				return false;
			}
		}
		return $code ? $plugin_file : true;
	}
}