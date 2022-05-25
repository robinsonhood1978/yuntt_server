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
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use common\models\UserModel;
use common\models\DistributeMerchantModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id DistributeController.php 2020.2.4 $
 * @author mosir
 */

class DistributeController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionMerchant()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
	
		if(!Yii::$app->request->isAjax)
		{
			$this->params['filtered'] = $this->getConditions($post);

			$this->params['_foot_tags'] = Resource::import('inline_edit.js');
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_merchant')]);
			return $this->render('../distribute.merchant.html', $this->params);
		}
		else
		{
			$query = DistributeMerchantModel::find()->select('dmid,userid,username,parent_id,phone_mob,name,status,created');
			$query = $this->getConditions($post, $query)->orderBy(['dmid' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			foreach ($list as $key => $value)
			{
				$list[$key]['parent'] = UserModel::find()->select('username')->where(['userid' => $value['parent_id']])->scalar();
				$list[$key]['status'] 	= $this->getStatus($value['status']);
				$list[$key]['created']	= Timezone::localDate('Y-m-d', $value['created']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 分销商审核
	 */
	public function actionVerify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
	
		if(!Yii::$app->request->isAjax)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_verify')]);
			return $this->render('../distribute.verify.html', $this->params);
		}
		else
		{
			$query = DistributeMerchantModel::find()->select('dmid,username,phone_mob,name,created,status')
				->where(['in', 'status', [Def::STORE_APPLYING, Def::STORE_NOPASS]])
				->orderBy(['dmid' => SORT_DESC]);
			
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			foreach ($list as $key => $value)
			{
				$list[$key]['status'] 	= $this->getStatus($value['status']);
				$list[$key]['created']	= Timezone::localDate('Y-m-d', $value['created']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 查看分销商并审核
	 */
	public function actionView()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!Yii::$app->request->isPost)
		{
			$record = DistributeMerchantModel::find()->where(['dmid' => $get->id])->asArray()->one();
			$record['status'] = $this->getStatus($record['status']);
			$record['parent'] = UserModel::find()->select('username')->where(['userid' => $record['parent_id']])->scalar();
			$this->params['distribute'] = $record;
			
			$this->params['page'] = Page::seo(['title' => Language::get('detail')]);
			return $this->render('../distribute.view.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = DistributeMerchantModel::findOne($get->id);

			// 待审核的店铺才允许提交，防止重复插入
			if($model && in_array($model->status, [Def::STORE_APPLYING,Def::STORE_NOPASS]))
			{
				// 批准
				if ($post->action == 'agree')
				{
					$model->status = Def::STORE_OPEN;
					$model->remark = '';
					if(!$model->save()) {
						return Message::warning(Language::get('handle_fail'));
					}

					return Message::display(Language::get('agree_ok'), ['distribute/merchant']);
				}
				// 拒绝
				elseif($post->action == 'reject')
				{
					if (!$post->reason) {
						return Message::warning(Language::get('input_reason'));
					}
					
					$model->remark = $post->reason;
					$model->status = Def::STORE_NOPASS;
					if(!$model->save()) {
						return Message::warning(Language::get('handle_fail'));
					}

					return Message::display(Language::get('reject_ok'), ['distribute/verify']);
				}
			}
			return Message::warning(Language::get('handle_error'));
		}
	}

	/**
	 * 删除分销商
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if($id && ($model = DistributeMerchantModel::findOne($id))) {
				if(!$model->delete()) {
					return Message::warning($model->errors);
				}
			}
		}
		return Message::display(Language::get('drop_ok'));
	}

	/**
	 * 导出数据
	 */
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = DistributeMerchantModel::find()->select('dmid,username,phone_mob,parent_id as parent,name,status,created');
		if(!empty($post->id)) {
			$query->andWhere(['in', 'dmid', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\DistributeMerchantExportForm::download($query->asArray()->all());		
	}
	
	private function getStatus($status = null)
	{
		$result = array(
            Def::STORE_APPLYING  => Language::get('applying'),
			Def::STORE_NOPASS	 => Language::get('nopass'),
            Def::STORE_OPEN      => Language::get('open'),
            Def::STORE_CLOSED    => Language::get('close'),
        );
		if($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['name', 'status', 'username', 'phone_mob', 'parent'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->name) {
			$query->andWhere(['like', 'name', $post->name]);
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		if($post->phone_mob) {
			$query->andWhere(['phone_mob' => $post->phone_mob]);
		}
		if($post->parent) {
			$userid = UserModel::find()->select('userid')->where(['username' => $post->parent])->scalar();
			$query->andWhere(['parent_id' => $userid]);
		}
		if(isset($post->status) && $post->status !== '') {
			$query->andWhere(['status' => intval($post->status)]);
		} else {
			$query->andWhere(['in', 'status', [Def::STORE_OPEN, Def::STORE_CLOSED]]);
		}

		return $query;
	}
}
