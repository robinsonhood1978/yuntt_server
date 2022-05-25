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

use common\models\MessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id My_messageController.php 2018.5.28 $
 * @author mosir
 */

class My_messageController extends \common\controllers\BaseUserController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$curmenu = isset($post->folder) ? $post->folder : 'newpm';
		
		// 取得列表数据	
		$model = new \frontend\models\My_messageForm();
		list($recordlist, $page) = $model->formData($post, 15);
		$this->params['messages'] = $recordlist;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_message'), Url::toRoute('my_message/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_message', $curmenu);

		$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
		return $this->render('../my_message.index.html', $this->params);
	}
	
	public function actionSend()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
        if (!Yii::$app->request->isPost)
		{
			// 取得列表数据	
			$model = new \frontend\models\My_messageSendForm();
			$this->params['to_username'] = $model->getUsersFromId($get);
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_message'), Url::toRoute('my_message/index'), Language::get('message_send'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_message', 'message_send');
			
			$this->params['page'] = Page::seo(['title' => Language::get('message_send')]);
			return $this->render('../my_message.send.html', $this->params);
		
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\My_messageSendForm();
            if(!$model->send($post, true, 0)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('send_message_successed'), ['my_message/index', 'folder' => 'privatepm']);
        }
    }
	
	public function actionView()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['msg_id']);
		
		$model = new \frontend\models\My_messageViewForm();
		if(!($message = $model->formData($get, true))) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			if(in_array($message['to_id'], [0, Yii::$app->user->id])) {
				MessageModel::updateAll(['new' => 0], ['msg_id' => $get->msg_id]);
			}
			$this->params['message'] = $message;
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_message'), Url::toRoute('my_message/index'), Language::get('message_view'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_message', 'message_view');

			$this->params['page'] = Page::seo(['title' => Language::get('message_view')]);
			return $this->render('../my_message.view.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			 if(!$model->send($post, $get, $message)) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('send_message_successed'));
		}
	}
	
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['msg_id']);
		
		$model = new \frontend\models\My_messageDeleteForm();
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
        return Message::display(Language::get('drop_message_successed'));
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'newpm',
                'url'   => Url::toRoute(['my_message/index']),
            ),
			array(
                'name'  => 'privatepm',
                'url'   => Url::toRoute(['my_message/index', 'folder' => 'privatepm']),
            ),
			array(
                'name'  => 'systempm',
                'url'   => Url::toRoute(['my_message/index', 'folder' => 'systempm']),
            ),
			array(
                'name'  => 'announcepm',
                'url'   => Url::toRoute(['my_message/index', 'folder' => 'announcepm']),
            )
        );
		if(in_array($this->action->id, ['view'])) {
			$submenus[] = array(
				'name' => 'message_view',
				'url'  => '',
			);
		}
		if(in_array($this->action->id, ['send'])) {
			$submenus[] = array(
				'name' => 'message_send',
				'url'  => '',
			);
		}

        return $submenus;
    }
}