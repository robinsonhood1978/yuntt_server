<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id Find_passwordController.php 2018.10.22 $
 * @author mosir
 */

class Find_passwordController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}
	
    public function actionIndex()
    {
		if(!Yii::$app->request->isPost)
		{
			// 禁止再次调回到找回密码页面
			if(!Yii::$app->user->isGuest) {
				return $this->redirect(['user/index']);
			}

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js',
				'style' => 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('find_password')]);
			return $this->render('../find_password.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Find_passwordForm();
			if(!($user = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('password_reset_redirect'), ['find_password/reset', 'key' => $user->activation]);
		}
    }
	
	public function actionReset()
    {
		// 设置密码后，禁止再次调回到设置页面
		if(!Yii::$app->user->isGuest) {
			return $this->redirect(['user/index']);
		}

		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!Yii::$app->request->isPost)
		{
			if(empty($get->key) || !UserModel::find()->where(['activation' => $get->key])->one()) {
				return Message::warning(Language::get('request_error'));
			}

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
     		$this->params['page'] = Page::seo(['title' => Language::get('password_reset')]);
			return $this->render('../find_password.reset.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Find_passwordResetForm();
			if(!$model->save($post, $get, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('password_reset_ok'), ['user/login']);	
        }
	}
}
