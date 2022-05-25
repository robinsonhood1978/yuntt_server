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

use common\models\UserModel;
use common\models\UserPrivModel;
use common\models\GoodsModel;
use common\models\StoreModel;
use common\models\SgradeModel;
use common\models\RegionModel;
use common\models\ScategoryModel;
use common\models\CategoryStoreModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id StoreController.php 2018.8.9 $
 * @author mosir
 */

class StoreController extends \common\controllers\BaseAdminController
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
		$this->params['sgrades'] = SgradeModel::getOptions();
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['stypes'] = ['personal' => Language::get('personal'), 'company' => Language::get('company')];
			$this->params['filtered'] = $this->getConditions($post);
			
			$this->params['_foot_tags'] = Resource::import('inline_edit.js');
			$this->params['page'] = Page::seo(['title' => Language::get('store_list')]);
			return $this->render('../store.index.html', $this->params);
		}
		else
		{
			$query = StoreModel::find()
				->select('store_id,store_name,stype,sgrade,owner_name,region_name,add_time,end_time,state,recommended,sort_order,tel')
				->orderBy(['sort_order' => SORT_ASC, 'store_id' => SORT_DESC]);

			$query = $this->getConditions($post, $query);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['stype'] = Language::get($value['stype']);
				$list[$key]['sgrade'] = $this->params['sgrades'][$value['sgrade']];
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d', $value['add_time']);
				$list[$key]['end_time'] = $value['end_time'] > 0 ? Timezone::localDate('Y-m-d', $value['end_time']) :'-';
				$list[$key]['state'] = $this->getStatus($value['state']);
				$list[$key]['username'] = UserModel::find()->select('username')->where(['userid' => $value['store_id']])->scalar();
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 店铺审核
	 */
	public function actionVerify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
		$this->params['sgrades'] = SgradeModel::getOptions();
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['page'] = Page::seo(['title' => Language::get('store_verify')]);
			return $this->render('../store.verify.html', $this->params);
		}
		else
		{
			$query = StoreModel::find()
				->select('store_id,store_name,stype,sgrade,owner_name,region_name,state')
				->where(['in', 'state', [Def::STORE_APPLYING, Def::STORE_NOPASS]])
				->orderBy(['store_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['stype'] = Language::get($value['stype']);
				$list[$key]['sgrade'] = $this->params['sgrades'][$value['sgrade']];
				$list[$key]['state'] = $this->getStatus($value['state']);
				$list[$key]['username'] = UserModel::find()->select('username')->where(['userid' => $value['store_id']])->scalar();
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionEdit()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$get->id || !($store = StoreModel::getInfo($get->id))) {
			return Message::warning(Language::get('no_such_store'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['store'] = array_merge($store, ['scate_id' => CategoryStoreModel::find()->select('cate_id')->where(['store_id' => $get->id])->scalar()]);
			$this->params['regions'] = RegionModel::getOptions(0);
			$this->params['scategories'] = ScategoryModel::getOptions();
			$this->params['sgrades'] = SgradeModel::getOptions();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,mlselection.js,jquery.plugins/timepicker/jquery-ui-timepicker-addon.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css,jquery.plugins/timepicker/jquery-ui-timepicker-addon.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('store_edit')]);
			return $this->render('../store.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id','sort_order', 'state', 'region_id', 'sgrade', 'recommended']);
			
			$model = new \backend\models\StoreForm(['store_id' => $get->id]);
			if(!($store = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['store/index']);
		}
	}
	
	public function actionBatch()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['regions'] = RegionModel::getOptions(0);
			$this->params['scategories'] = ScategoryModel::getOptions();
			$this->params['sgrades'] = SgradeModel::getOptions();
			
			$this->params['_foot_tags'] = Resource::import('mlselection.js');
			
			$this->params['page'] = Page::seo(['title' => Language::get('batch_edit')]);
			return $this->render('../store.batch.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'sort_order', 'region_id', 'sgrade', 'recommended']);
			
			foreach(explode(',', Yii::$app->request->get('id', 0)) as $id) {
				$model = new \backend\models\StoreForm(['store_id' => $id]);
				if(!($store = $model->save($model->batchFormData($post), true))) {
					return Message::warning($model->errors);
				}
			}
			return Message::display(Language::get('edit_ok'), ['store/index']);
		}
	}
	
	 /**
	  * 查看并处理店铺申请 
	  */
	public function actionView()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$get->id || !($store = StoreModel::getInfo($get->id))) {
			return Message::warning(Language::get('no_such_store'));
		}

		if(!Yii::$app->request->isPost)
		{
            $sgrades = SgradeModel::getOptions();
            $store['sgrade'] = $sgrades[$store['sgrade']];
			$this->params['store'] = $store;

			$this->params['page'] = Page::seo(['title' => Language::get('store_view')]);
			return $this->render('../store.view.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			// 待审核的店铺才允许提交，防止重复插入
			if($store['state'] == Def::STORE_APPLYING)
			{
				// 批准
				if ($post->action == 'agree')
				{
					$model = StoreModel::findOne($get->id);
					$model->state = Def::STORE_OPEN;
					$model->apply_remark = '';
					if(!$model->save()) {
						return Message::warning(Language::get('agree_fail'));
					}
					
					// 给商家赠送开店积分
					IntegralModel::updateIntegral([
						'userid'  => $store['store_id'],
						'type'    => 'openshop',
						'amount'  => IntegralSettingModel::getSysSetting('openshop')
					]);

					$pmer = Basewind::getPmer('toseller_store_open_notify', ['store' => $store]);
					if($pmer) {
						$pmer->sendFrom(0)->sendTo($store['store_id'])->send();
					}
					return Message::display(Language::get('agree_ok'), ['store/index']);
				}
				// 拒绝
				elseif($post->action == 'reject')
				{
					if (!$post->reason) {
						return Message::warning(Language::get('input_reason'));
					}
					
					$model = StoreModel::findOne($get->id);
					$model->apply_remark = $post->reason;
					$model->state = Def::STORE_NOPASS;
					if(!$model->save()) {
						return Message::warning(Language::get('reject_fail'));
					}
	
					$pmer = Basewind::getPmer('toseller_store_refused_notify', ['store' => $store]);
					if($pmer) {
						$pmer->sendFrom(0)->sendTo($store['store_id'])->send();
					}
					return Message::display(Language::get('reject_ok'), ['store/verify']);
				}
				return Message::warning(Language::get('handle_fail'));	
			}
			return Message::display(Language::get('agree_ok'), ['store/index']);
		}
	}
	
	/**
	 * 删除店铺
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if($id && ($model = StoreModel::findOne($id))) {
				if(!$model->delete()) {
					return Message::warning($model->errors);
				}
				// 删除店铺管理权限表
				UserPrivModel::deleteAll(['store_id' => $id]);

				// 设置商品为禁售（不建议删除）
				GoodsModel::updateAll(['if_show' => 0, 'closed' => 1], ['store_id' => $id]);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}

	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = StoreModel::find()->alias('s')->select('s.store_id,s.store_name,s.stype, s.sgrade,s.owner_name,s.region_name,s.add_time,s.state,s.recommended,s.tel,u.username')
			->joinWith('user u', false)
			->orderBy(['sort_order' => SORT_ASC, 'store_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'store_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\StoreExportForm::download($query->asArray()->all());		
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'recommended', 'sort_order']);
		if(in_array($post->column, ['recommended', 'sort_order'])) {
			
			$model = new \backend\models\StoreForm(['store_id' => $post->id]);
			$query = StoreModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!$model->save($query, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
	
	/* 新增用户走势（图表）本月和上月的数据统计 */
	public function actionTrend()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		list($curMonthQuantity, $curDays, $beginMonth, $endMonth) = $this->getMonthTrend(Timezone::gmtime());
		list($preMonthQuantity, $preDays) = $this->getMonthTrend($beginMonth - 1);
		
		$series = array($curMonthQuantity, $preMonthQuantity);
		$legend = array('本月新增店家数','上月店家数');
		
		$days = $curDays > $preDays ? $curDays : $preDays;
		
		// 获取日期列表
		$xaxis = array();
		for($day = 1; $day <= $days; $day++) {
			$xaxis[] = $day;
		}

		$this->params['echart'] = array(
			'id'		=>  mt_rand(),
			'theme' 	=> 'macarons',
			'width'		=> '100%',
			'height'    => 360,
			'option'  	=> json_encode([
				'grid' => ['left' => '20', 'right' => '20', 'top' => '80', 'bottom' => '20', 'containLabel' => true],
				'tooltip' 	=> ['trigger' => 'axis'],
				'legend'	=> [
					'data' => $legend
				],
				'calculable' => true,
   				'xAxis' => [
        			[
						'type' => 'category', 
						'data' => $xaxis
        			]
    			],
				'yAxis' => [
        			[
            			'type' => 'value'
        			]
   				 ],
				 'series' => [
					[
						'name' => $legend[0],
						'type' => 'bar',
						'data' => $series[0],
					],
					[
						'name' => $legend[1],
						'type' => 'bar',
						'data' => $series[1],
					]
				]
			])
		);
		
		return $this->render('../echarts.html', $this->params);
	}
	
	/* 月数据统计 */
	private function getMonthTrend($month = 0)
	{
		// 本月
		if(!$month) $month = Timezone::gmtime();
		
		// 获取当月的开始时间戳和结束那天的时间戳
		list($beginMonth, $endMonth) = Timezone::getMonthDay(Timezone::localDate('Y-m', $month));
		
		$list = StoreModel::find()->select('add_time')->where(['>=', 'add_time', $beginMonth])->andWhere(['<=', 'add_time', $endMonth])->andWhere(['!=', 'state',0])->asArray()->all();
		
		// 该月有多少天
		$days = round(($endMonth-$beginMonth) / (24 * 3600));
		
		// 按天算归类
		$quantity = array();
		foreach($list as $key => $val)
		{
			$day = Timezone::localDate('d', $val['add_time']);
	
			if(isset($quantity[$day-1])) {
				$quantity[$day-1]++;
			}
			else {
				$quantity[$day-1] = 1;
			}
		}
		
		// 给天数补全
		for($day = 1; $day <= $days; $day++)
		{
			if(!isset($quantity[$day-1])) {
				$quantity[$day-1] = 0;
			}
		}
		// 按日期顺序排序
		ksort($quantity);

		return array($quantity, $days, $beginMonth, $endMonth);
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
				if(in_array($field, ['store_name', 'stype', 'sgrade', 'owner_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->store_name) {
			$query->andWhere(['like', 'store_name', $post->store_name]);
		}
		if($post->stype) {
			$query->andWhere(['stype' => $post->stype]);
		}
		if($post->sgrade) {
			$query->andWhere(['sgrade' => $post->sgrade]);
		}
		if($post->owner_name) {
			$query->andWhere(['or', ['owner_name' => $post->owner_name], ['username' => $post->owner_name]]);
		}
		if(isset($post->state) && $post->state !== '') {
			$query->andWhere(['state' => intval($post->state)]);
		} else {
			$query->andWhere(['in', 'state', [Def::STORE_OPEN, Def::STORE_CLOSED]]);
		}

		return $query;
	}
}
