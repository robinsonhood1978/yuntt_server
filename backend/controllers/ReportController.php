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

use common\models\ReportModel;
use common\models\UserModel;
use common\models\GoodsModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id ReportController.php 2018.9.12 $
 * @author mosir
 */

class ReportController extends \common\controllers\BaseAdminController
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
			$this->params['search_options'] = $this->getSearchOption();
			
			$this->params['_foot_tags'] = Resource::import('inline_edit.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('report_list')]);
			return $this->render('../report.index.html', $this->params);
		}
		else
		{
			$query = ReportModel::find()->alias('r')->select('r.id,r.userid,r.content,r.status,r.add_time,s.store_id,s.store_name,g.goods_id,g.goods_name')
				->joinWith('store s', false)
				->joinWith('goods g', false);
			$query = $this->getConditions($post, $query)->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['username'] = UserModel::find()->select('username')->where(['userid' => $value['userid']])->scalar();
			}
			
			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionVerify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		if(!$post->id || !($list = ReportModel::find()->select('id,userid,goods_id,add_time')->where(['and', ['in', 'id', explode(',', $post->id)], ['status' => 0]])->indexBy('id')->asArray()->all())){
			return Message::warning(Language::get('no_such_item'));
		}

		ReportModel::updateAll(['status' => 1, 'examine' => $this->visitor['username'], 'verify' =>  $post->verify], ['in', 'id', array_keys($list)]);
		
		// 通知举报人（站内信）
		foreach($list as $key => $value) {
			$value['content'] = $post->verify;
			$value['username'] = UserModel::find()->select('username')->where(['userid' => $value['userid']])->scalar();
			$value['goods_name'] = GoodsModel::find()->select('goods_name')->where(['goods_id' => $value['goods_id']])->scalar();

			$pmer = Basewind::getPmer('touser_report', ['report' => $value]);
			if($pmer) {
				$pmer->sendFrom(0)->sendTo($value['userid'])->send();
			}
		}

		return Message::display(Language::get('verify_ok'));
	}	
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id){
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!ReportModel::deleteAll(['in', 'id', explode(',', $post->id)])) {
			return Message::warning(Language::get('drop_fail'));	
		}
		return Message::display(Language::get('drop_ok'), ['report/index']);
	}
	
	private function getSearchOption()
	{
		return array(
            'username'		=> Language::get('username'),
            'goods_name' 	=> Language::get('report_goods'),
            'store_name' 	=> Language::get('report_store'),
		);
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'goods_name', 'store_name', 'status'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->field && $post->search_name && in_array($post->field, ['username', 'goods_name', 'store_name']))
		{
			if($post->field == 'username'){
				$user = UserModel::find()->select('userid')->where(['username' => $post->search_name])->one();
				$query->andWhere(['u.userid' => $user->userid]);
			}
			
			if($post->field == 'goods_name'){
				$allId = GoodsModel::find()->select('goods_id')->where(['like', 'goods_name', $post->search_name])->column();
				$query->andWhere(['in', 'g.goods_id', $allId]);
			}
			
			if($post->field == 'store_name'){
				$store = StoreModel::find()->select('store_id')->where(['store_name' => $post->search_name])->one();;
				$query->andWhere(['s.store_id' => $store->store_id]);
			}
		}
		
		if(isset($post->status) && in_array($post->status, [0,1])) {
			$query->andWhere(['status' => $post->status]);
		}

		return $query;
	}
}
