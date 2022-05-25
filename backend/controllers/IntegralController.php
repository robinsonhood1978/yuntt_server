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

use common\models\IntegralModel;
use common\models\IntegralLogModel;
use common\models\IntegralSettingModel;
use common\models\UserModel;
use common\models\SgradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id IntegralController.php 2018.8.6 $
 * @author mosir
 */

class IntegralController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);

			$this->params['_head_tags'] = Resource::import(['style' => 'dialog/dialog.css']);
			$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.ui/jquery.ui.js,dialog/dialog.js']);
		
			$this->params['page'] = Page::seo(['title' => Language::get('integral_manage')]);
			return $this->render('../integral.index.html', $this->params);
		}
		else
		{
			$query = UserModel::find()->alias('u')->select('u.userid,u.username,u.phone_mob,i.amount')->joinWith('integral i', false);
			$query = $this->getConditions($post, $query);
	
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			// foreach($list as $key => $value) {
			// 	$list[$key]['amount'] = floatval($value['amount']);
			// }

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionLogs() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'limit', 'page']);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_user'));
		}
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['page'] = Page::seo(['title' => Language::get('integral_logs')]);
			return $this->render('../integral.logs.html', $this->params);
		}
		else
		{
			$query = IntegralLogModel::find()->select('log_id,userid,type,changes,balance,flag,add_time,state,order_id,order_sn')->where(['userid' => $post->id])->orderBy(['log_id' => SORT_DESC]);
	
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			foreach ($list as $key => $value)
			{
				$list[$key]['username'] = UserModel::find()->select('username')->where(['userid' => $value['userid']])->scalar();
				$list[$key]['type'] = Language::get($value['type']);
				$list[$key]['state'] = IntegralModel::getStatusLabel($value['state']);
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionRecharge()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($user = UserModel::find()->alias('u')->select('u.userid,u.username,i.amount')->joinWith('integral i', false)->where(['u.userid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_user'));
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['user'] = $user;
			
			$this->params['page'] = Page::seo(['title' => Language::get('integral_recharge')]);
			return $this->render('../integral.recharge.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			if(!IntegralSettingModel::getSysSetting('enabled')) {
				return Message::popWarning(Language::get('recharge_valid'));
			}
			
			$model = new \backend\models\IntegralRechargeForm(['userid' => $id]);
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors ? $model->errors : Language::get('recharge_fail'));
			}

			return Message::popSuccess();
		}
	}
	
	public function actionSetting()
	{
		if(!Yii::$app->request->isPost)
		{
			$sgrades = SgradeModel::find()->select('grade_id,grade_name')->asArray()->all();
			foreach($sgrades as $key => $val) {
				$sgrades[$key]['buygoods'] = IntegralSettingModel::getSysSetting(['buygoods', $val['grade_id']]);
			}
			$this->params['sgrades'] = $sgrades;
			$this->params['setting'] = IntegralSettingModel::getSysSetting();
			
			$this->params['page'] = Page::seo(['title' => Language::get('integral_setting')]);
			return $this->render('../integral.setting.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\IntegralSettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = UserModel::find()->alias('u')->select('u.userid,u.username,i.amount')->joinWith('integral i', false)->orderBy(['userid' => SORT_ASC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'u.userid', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\IntegralExportForm::download($query->asArray()->all());
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'phone_mob'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		if($post->phone_mob) {
			$query->andWhere(['phone_mob' => $post->phone_mob]);
		}
		return $query;
	}
}