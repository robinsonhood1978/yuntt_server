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
use common\library\Page;
use common\library\Setting;

/**
 * @Id SettingController.php 2018.9.3 $
 * @author mosir
 */

class SettingController extends \common\controllers\BaseAdminController
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
		if(!Yii::$app->request->isPost) 
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			$this->params['time_zone'] = Setting::getTimezone();
			
			$this->params['page'] = Page::seo(['title' => Language::get('base_setting')]);
			return $this->render('../setting.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_base_setting_successed'), ['setting/index']);
		}
	}
	
	public function actionEmail()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			
			$this->params['page'] = Page::seo(['title' => Language::get('email_setting')]);
			return $this->render('../setting.email.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_email_setting_successed'), ['setting/email']);
		}
	}
	public function actionVerifycode()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			
			$this->params['page'] = Page::seo(['title' => Language::get('captcha_setting')]);
			return $this->render('../setting.verifycode.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_captcha_setting_successed'), ['setting/verifycode']);
		}
	}
	public function actionStore()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			
			$this->params['page'] = Page::seo(['title' => Language::get('store_setting')]);
			return $this->render('../setting.store.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_store_setting_successed'), ['setting/store']);
		}
	}
	
	public function actionApi()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			
			$this->params['page'] = Page::seo(['title' => Language::get('api_setting')]);
			return $this->render('../setting.api.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_api_setting_successed'), ['setting/api']);
		}
	}
}
