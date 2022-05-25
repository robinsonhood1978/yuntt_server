<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model; 
use yii\helpers\ArrayHelper;

use common\models\PluginModel;

use common\library\Language;
use common\library\Plugin;

/**
 * @Id PluginForm.php 2018.9.5 $
 * @author mosir
 */

class PluginForm extends Model
{
	/**
	 * 插件类代码
	 */
	public $instance = null;

	/**
	 * 具体插件实例
	 */
	public $code = null;

	/**
	 * 错误抓取
	 */
	public $errors = null;

	public function valid($post)
	{
		if(!$this->instance || !$this->code) {
			$this->errors = Language::get('no_such_plugin');
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		$model = PluginModel::find()->where(['instance' => $this->instance, 'code' => $this->code])->one();
		if(!$model) {
			$model = new PluginModel();

			$pluginInfo = Plugin::getInstance($this->instance)->build($this->code)->getInfo();
			$model->name = $pluginInfo['name'];
			$model->subname = isset($pluginInfo['subname']) ? $pluginInfo['subname'] : '';
			$model->desc = $pluginInfo['desc'];
		}
		$model->instance = $this->instance;
		$model->code = $this->code;
		$model->config = !empty($post->config) ? serialize(ArrayHelper::toArray($post->config)) : '';
		$model->enabled = isset($post->enabled) ? $post->enabled : 1;

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		$model = PluginModel::find()->where(['instance' => $this->instance, 'code' => $this->code])->one();
		if(!$model->delete()) {
			$this->errors = Language::get('plugin_uninstall_fail');
			return false;
		}

		return true;
	}
}
