<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins;

use yii;

use common\models\PluginModel;

/**
 * @Id BasePlugin.php 2018.6.4 $
 * @author mosir
 */
 
class BasePlugin
{
	/**
	 * 插件系列
	 * @var string $instance
	 */
	protected $instance = null;

	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = null;

	/**
	 * 插件配置信息
	 * @var array $config
	 */
	public $config = null;

	/**
	 * 错误抓取
	 * @var string $errors
	 */
	public $errors = null;

	/**
	 * 页面提交参数
	 * @var object $params
	 */
	public $params = null;
	
	/**
	 * 构造函数
	 */
	public function __construct($params = null) 
	{
		if($this->config === null) {
			$this->config = $this->getConfig();
		}

		$this->params = $params;
	}
	
	/**
	 * 获取插件配置信息 
	 * @var $code 具体插件实例代码
	 */
	public function getConfig()
	{
		if(($query = PluginModel::find()->select('config')->where(['instance' => $this->instance, 'code' => $this->code])->one())) {
			if($query->config) {
				$query->config = unserialize($query->config);
			}
			return is_array($query->config) ? $query->config : array();
		}
		return array();
	}
	
	/** 
	 * 获取插件文件信息 
	 * @param string $code 获取插件列表需要该参数
	 * @var int $enabled字段用于在安装/配置插件时控制是否启用和关闭
	 */
	public function getInfo($code = null)
    {
		if(!$code) $code = $this->code;

        $plugin_file = Yii::getAlias('@common') .  '/plugins/' . $this->instance .'/' . $code . '/plugin.info.php';
		if(is_file($plugin_file)) {
			$result = include($plugin_file);
			if(($array = PluginModel::find()->select('enabled')->where(['instance' => $this->instance, 'code' => $code])->asArray()->one())) {
				$result = array_merge($result, $array);
			}
			return $result;
		}
		return array();
    }
	
	/**
	 * 获取插件列表 
	 * @var bool chceckInstall
	 */
	public function getList($checkInstall = false)
	{
        $plugin_dir = Yii::getAlias('@common') . '/plugins/' . $this->instance;
        static $plugins = null;
        if ($plugins === null)
        {
            $plugins = array();
            if (!is_dir($plugin_dir))
            {
                return $plugins;
            }
            $dir = dir($plugin_dir);
            while (false !== ($entry = $dir->read()))
            {
                //if (in_array($entry, array('.', '..')) || $entry{0} == '.' || $entry{0} == '$') { // for php >= 7.4 disabled
				if (in_array($entry, ['.', '..']) || in_array(substr($entry, 0, 1), ['.', '$'])) {
                    continue;
                }
				$info = $this->getInfo($entry);
				if(!$info) {
					continue;
				}
                $plugins[$entry] = $info;
				
				if($checkInstall) {
					$plugins[$entry]['isInstall'] = $this->isInstall($entry);
				}
            }
			$dir->close();
        }

        return $plugins;
	}
	
	/**
	 * 判断插件是否安装 
	 * @param string $code 获取插件列表需要该参数
	 */
	public function isInstall($code = null)
    {
		if(!$code) $code = $this->code;

		if(PluginModel::find()->where(['instance' => $this->instance, 'code' => $code])->exists()) {
			return true;
		}
		return false;
	}

	/**
	 * 获取插件code
	 * @desc $code因是受保护变量，不能直接获取
	 */
	public function getCode()
	{
		return $this->code;
	}
	
	public function setErrors($errors = null)
	{
		$this->errors = $errors;
	}
	public function getErrors()
	{
		return $this->errors;
	}
}