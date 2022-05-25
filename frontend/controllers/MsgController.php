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
use yii\helpers\Url;

use common\models\MsgModel;
use common\models\MsgLogModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Plugin;
use common\library\Resource;

/**
 * @Id MsgController.php 2018.4.18 $
 * @author mosir
 */

class MsgController extends \common\controllers\BaseUserController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('user'));
	}

    public function actionIndex()
    {
		if(!Yii::$app->request->isPost)
		{
			// 初始化检查
			if(!($smser = Plugin::getInstance('sms')->autoBuild()) || !$smser->verify(false)) {
				return Message::warning(Language::get('msgkey_not_config'));
			}
			
			$msg = MsgModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->one();
			if($msg) {
				$msg['sendTotal'] = MsgLogModel::find()->where(['userid' => Yii::$app->user->id,'status' => 1, 'type' => 0])->sum('quantity');
				$this->params['msg'] = $msg;
			}
			$this->params['redirect'] = Url::toRoute(['msg/index']);
			$this->params['functions'] = Plugin::getInstance('sms')->build()->getFunctions();

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');

			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('msg'), Url::toRoute('msg/index'), Language::get('msg_set'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('msg', 'msg_set');
			
			$this->params['page'] = Page::seo(['title' => Language::get('msg_set')]);
			return $this->render('../msg.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['state']);

			if(!$this->visitor['phone_mob']) {
				return Message::warning(Language::get('please_bind_phone'));
			}

			if(!($model = MsgModel::find()->where(['userid' => Yii::$app->user->id])->one())) {
				$model = new MsgModel();
			}
			$model->userid = Yii::$app->user->id;
			$model->state = $post->state ? 1 : 0;
			$model->functions = implode(',', (array)$post->functions);
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('set_ok'));
		}
    }
	
	public function actionLogs()
    {
		$query = MsgLogModel::find()->where(['userid' => Yii::$app->user->id, 'status' => 1, 'type' => 0])->orderBy(['id' => SORT_DESC]);
		$page = Page::getPage($query->count(), 20);
		$msgLogs = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['msgLogs'] = $msgLogs;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('msg'), Url::toRoute('msg/index'), Language::get('msg_logs'));
			
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('msg', 'msg_logs');
			
		$this->params['page'] = Page::seo(['title' => Language::get('msg_logs')]);
		return $this->render('../msg.logs.html', $this->params);
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'msg_set',
                'url' => Url::toRoute('msg/index'),
            ),
			array(
                'name' => 'msg_logs',
                'url' => Url::toRoute('msg/logs'),
            ),
		);

        return $submenus;
    }
}