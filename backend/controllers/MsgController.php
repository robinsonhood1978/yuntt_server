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
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;

use common\models\MsgModel;
use common\models\MsgLogModel;
use common\models\MsgTemplateModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id MsgController.php 2018.8.23 $
 * @author mosir
 */

class MsgController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	/**
	 * 短信发送页面
	 */
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			// 发送平台列表数组，从这里读取平台名称
			foreach(Plugin::getInstance('sms')->build()->getList() as $key => $value) {
				$this->params['smslist'][$key] = $value['name'];
			}

			$this->params['filtered'] = $this->getConditions($post);
			$this->params['status_list'] = array(Language::get('send_failed'), Language::get('send_success'));
			
			$this->params['page'] = Page::seo(['title' => Language::get('sendlog')]);
			return $this->render('../msg.index.html', $this->params);
		}
		else
		{
			$query = MsgLogModel::find()->select('id,userid,quantity,status,message,add_time,content,receiver,code')->where(['type' => 0]);
			$query = $this->getConditions($post, $query)->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				if(($array = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one())) {
					$list[$key] = array_merge($value, $array);
				}

				$list[$key]['code'] = Plugin::getInstance('sms')->build()->getInfo($value['code'])['name'];
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 短信充值记录
	 */
	public function actionRecharge()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);

			$this->params['page'] = Page::seo(['title' => Language::get('msgrecharge')]);
			return $this->render('../msg.recharge.html', $this->params);
		}
		else
		{
			$query = MsgLogModel::find()->select('id,userid,quantity,status,message,add_time')->where(['type' => 1]);
			$query = $this->getConditions($post, $query)->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				if(($array = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one())) {
					$list[$key] = array_merge($value, $array);
				}

				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionUser()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['status_list'] = array(Language::get('closed'), Language::get('enable'));
			
			$this->params['page'] = Page::seo(['title' => Language::get('msguser')]);
			return $this->render('../msg.user.html', $this->params);
		}
		else
		{
			$query = MsgModel::find()->select('id,num,state,userid,functions');
			$query = $this->getConditions($post, $query)->orderBy(['id' => SORT_DESC]);
			
			// 发送短信的场景
			$functions = Plugin::getInstance('sms')->build()->getFunctions();

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				foreach($functions as $v) {
					$list[$key][$v] = in_array($v, explode(',', $value['functions'])) ? 1 : 0;
				}

				if(($array = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one())) {
					$list[$key] = array_merge($value, $array);
				}
			}
			
			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	/* 分配短信 */
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);
			$this->params['user'] = UserModel::find()->select('userid,username')->where(['userid' => $post->userid])->asArray()->one();
			
			$this->params['page'] = Page::seo(['title' => Language::get('msgadd')]);
			return $this->render('../msg.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['num']);
			
			$model = new \backend\models\MsgForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('handle_ok'), ['msg/recharge']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			MsgLogModel::deleteAll(['in', 'id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}

	/**
	 * 短信模板列表页面
	 */
	public function actionTemplate()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			
			$this->params['page'] = Page::seo(['title' => Language::get('msgtemplate')]);
			return $this->render('../msg.template.html', $this->params);
		}
		else
		{
			$query = MsgTemplateModel::find()->select('id,code,templateId,content,signName,scene,add_time');
			$query = $this->getConditions($post, $query)->orderBy(['id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['name'] = Plugin::getInstance('sms')->build()->getInfo($value['code'])['name'];
				$list[$key]['scene'] = Language::get($value['scene']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 新增/编辑短信模板
	 * 阿里大鱼短信平台需要短信模板
	 */
	public function actionAddtemplate()
	{
		$id = Yii::$app->request->get('id', 0);

		if(!Yii::$app->request->isPost)
		{
			if($id && ($template = MsgTemplateModel::find()->where(['id' => $id])->asArray()->one())) {
				$this->params['template'] = $template;
			}

			$smser = Plugin::getInstance('sms')->build();

			// 发送平台列表数组，从这里读取平台名称
			foreach($smser->getList() as $key => $value) {
				$this->params['smslist'][$key] = $value['name'];
			}

			// 发送短信的场景
			$this->params['scenelist'] = $smser->getFunctions(true);
			
			$this->params['page'] = Page::seo(['title' => Language::get('addtemplate')]);
			return $this->render('../msg.template.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\MsgTemplateForm();
			$model->id = $id;
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('handle_ok'), ['msg/template']);
		}
	}

	/**
	 * 删除短信模板
	 */
	public function actionDeletetemplate()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			MsgTemplateModel::deleteAll(['in', 'id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionSend()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('sendtest')]);
			return $this->render('../msg.send.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(empty($post->content)) {
				return Message::warning(Language::get('content_no_null'));
			}

			$smser = Plugin::getInstance('sms')->autoBuild();
			if(!$smser) {
				return Message::warning(Language::get('send_failed'));
			}

			$smser->receiver = $post->receiver;
			if(!$smser->testsend($post->content)) {
				return Message::warning($smser->errors);
			}

			return Message::display(Language::get('send_success'), ['msg/index']);
		}
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = MsgLogModel::find()->alias('ml')->select('ml.*,u.username')->joinWith('user u', false)->indexBy('id')->where(['ml.type' => 0])->orderBy(['id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_such_msg'));
		}
		return \backend\models\MsgLogExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'receiver', 'code', 'status', 'phone_mob', 'type', 'templateId'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$userid = UserModel::find()->select('userid')->where(['username' => $post->username])->scalar();
			$query->andWhere(['userid' => $userid]);
		}
		// 针对发送记录才有
		if(isset($post->receiver) && $post->receiver) {
			$query->andWhere(['like', 'receiver', $post->receiver]);
		}
		// 针对发送记录才有
		if(isset($post->status) && $post->status !== '') {
			$query->andWhere(['status' => ($post->status == 1) ? 1 : 0]);
		}
		// 针对发送记录才有
		if($post->code) {
			$query->andWhere(['code' => $post->code]);
		}
		// 针对短信用户才有
		if(isset($post->phone_mob) && $post->phone_mob) {
			$userid = UserModel::find()->select('userid')->where(['phone_mob' => $post->phone_mob])->scalar();
			$query->andWhere(['userid' => $userid]);
		}
		// 针对短信用户才有
		if(isset($post->state) && $post->state != '') {
			$query->andWhere(['state' => ($post->state == 1) ? 1 : 0]);
		}
		// 针对新增/编辑短信模板才有
		if($post->templateId) {
			$query->andWhere(['templateId' => $post->templateId]);
		}

		return $query;
	}
}
