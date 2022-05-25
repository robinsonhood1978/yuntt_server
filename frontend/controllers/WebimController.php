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
use common\models\WebimOnlineModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Def;

/**
 * @Id WebimController.php 2018.10.24 $
 * @author mosir
 */

class WebimController extends \common\controllers\BaseUserController
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
	
	/**
	 * 排除特定Action外，其他需要登录后访问
	 * @param $action
	 * @var array $extraAction
	 */
	public function beforeAction($action)
    {
		$this->extraAction = ['friend'];
		return parent::beforeAction($action);
    }
	
	public function actionFriend()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$model = new \frontend\models\WebimFriendForm();
		return $model->formData();
	}
	
	/* 获取当前访客或者指定客服的信息 */
	public function actionUser()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['toid']);
		
		$model = new \frontend\models\WebimUserForm();
		$result = $model->formData($post);
		return Message::result($result);	
	}
	
	/* 显示在对话框的最新的聊天记录 */
	public function actionLastchat()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'limit']);
		
		$model = new \frontend\models\WebimLastchatForm();
		$result = $model->formData($post);
		return Message::result($result);
	}
	
	/* 所有聊天记录 */
	public function actionChatlog()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'page']);
		
		$model = new \frontend\models\WebimChatlogForm();
		list($list, $page) = $model->formData($post);
		
		// 首次加载，显示最后一页的内容，而非第一页（这样才符合聊天记录的现实情况）
		// 由于此处不好实现重写分页，暂时先用跳转来处理（这样不好的地方是：有可能最末页显示很少的记录，而前一页是满页
		if(!isset($post->page)) {
			return $this->redirect(['webim/chatlog', 'id' => $post->id, 'type' => $post->type, 'page' => $page->getPageCount()]);
		}
		
		$this->params['list'] = $list;
		$this->params['pagination'] = Page::formatPage($page, true, 'basic');
		
		$this->params['page'] = Page::seo(['title' => Language::get('chatlog')]);
        return $this->render('../webim.chatlog.html', $this->params);
	}
	
	/* 检查是否禁用用户发送信息 */
	public function actionImforbid()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['uid']);
		
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if(!$post->uid || UserModel::find()->select('imforbid')->where(['userid' => $post->uid])->scalar()) {
			return true;
		}
		return false;
	}
	
	/* 用户下线 */
	public function actionLogout()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['uid']);
		$token = 'abcdefghijklmn1234556789';
		
		// 签名验证，防止人为恶意删除，导致下线用户不准确		
		if($post->uid && md5($post->uid.$token) == $post->sign) {
			WebimOnlineModel::deleteAll(['userid' => $post->uid]);
		}
	}
	
	/* 获取所有在线的用户-上线的时候才会执行（发言不执行）*/
	public function actionOnline()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['uid']);
		$token = 'abcdefghijklmn1234556789';
		
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$model = new \frontend\models\WebimOnlineForm(['token' => $token]);
		return $model->formData($post);
	}
	
	public function actionTalk()
	{
		$post = Basewind::trimAll(Yii::$app->request->post(), true, ['from','to']);
		$token = 'abcdefghijklmn1234556789';
		
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		$model = new \frontend\models\WebimTalkForm(['token' => $token]);
		return $model->save($post);
	}
	
	/* 在聊天窗上传图片 */
	public function actionUpload()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		if(($image = UploadedFileModel::getInstance()->upload('file', 0, Def::BELONG_WEBIM))) {
			$result = ['code' => 0, 'msg' => '', 'data' => ['src' => Page::urlFormat($image)]];
		}
		else {
			$result = ['code' => 1, 'msg' => Language::get('upload_fail'), 'data' => null];
		}
		return $result;
	}
}