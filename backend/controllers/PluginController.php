<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id PluginController.php 2018.9.4 $
 * @author mosir
 */

class PluginController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		if(!Plugin::isExisted($post->instance)) {
			return Message::warning(Language::get('no_such_plugin'));
		}
		
		$this->params['plugins'] = Plugin::getInstance($post->instance)->build()->getList(true);
		$this->params['instance'] = $post->instance;
		
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('plugin_'.$post->instance)]);
		return $this->render('../plugin.index.html', $this->params);
	}
	
	public function actionInstall()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);

		if(!Plugin::isExisted($get->instance, $get->code)) {
			return Message::warning(Language::get('no_such_plugin'));
		}
		
		if (!Yii::$app->request->isPost)
        {
			$model = Plugin::getInstance($get->instance)->build($get->code);
			$this->params['plugin'] = $model->getInfo();

			$this->params['page'] = Page::seo(['title' => Language::get('plugin_install')]);
            return $this->render('../plugin.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['enabled']);
			
			$model = new \backend\models\PluginForm(['instance' => $get->instance, 'code' => $get->code]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('install_plugin_successed'), ['plugin/index', 'instance' => $get->instance]);
        }
	}
	
	public function actionUninstall()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!Plugin::isExisted($post->instance, $post->code)) {
			return Message::warning(Language::get('no_such_plugin'));
		}
		
		$model = new \backend\models\PluginForm(['instance' => $post->instance, 'code' => $post->code]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('uninstall_plugin_successed'), ['plugin/index']);
	}
	
	public function actionConfig()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!Plugin::isExisted($get->instance, $get->code)) {
			return Message::warning(Language::get('no_such_plugin'));
		}
		
		if (!Yii::$app->request->isPost)
        {
			$model = Plugin::getInstance($get->instance)->build($get->code);
			$this->params['plugin'] = $model->getInfo();
			$this->params['config'] = $model->getConfig();

			$this->params['page'] = Page::seo(['title' => Language::get('plugin_config')]);
            return $this->render('../plugin.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['enabled']);
			
			$model = new \backend\models\PluginForm(['instance' => $get->instance, 'code' => $get->code]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('config_plugin_successed'), ['plugin/index', 'instance' => $get->instance]);
        }
	}
}
