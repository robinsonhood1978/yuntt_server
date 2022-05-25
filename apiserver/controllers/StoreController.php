<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\CollectModel;
use common\models\RegionModel;
use common\models\ScategoryModel;
use common\models\SgradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Promotool;

use apiserver\library\Respond;
use apiserver\library\Formatter;


/**
 * @Id StoreController.php 2018.12.5 $
 * @author yxyc
 */

class StoreController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 获取店铺列表
	 * @api 接口访问地址: http://api.xxx.com/store/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'sgrade', 'region_id', 'page', 'page_size']);
		$query = StoreModel::find()->alias('s')->select('s.store_id,s.store_name,s.tel,s.credit_value,s.praise_rate,s.stype,s.sgrade,s.add_time,s.store_logo,s.im_qq,s.region_id,s.address,cs.cate_id')->joinWith('categoryStore cs', false)->where(['state' => Def::STORE_OPEN]);
		$query = $this->getConditions($query, $post);
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatImagesUrl($value);
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);

			// 店铺在售商品总数
			$list[$key]['goods_count'] = GoodsModel::getCountOfStore($value['store_id']);

			// 店铺被收藏数
			$list[$key]['collects'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $value['store_id']])->count();

			// 店铺分类
			$list[$key]['cate_name'] = ScategoryModel::find()->select('cate_name')->where(['cate_id' => $value['cate_id']])->scalar();

			// 店铺所在地省市区地址信息
			$list[$key] = array_merge($list[$key], (array) RegionModel::getArrayRegion($value['region_id']));
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取店铺单条信息
	 * @api 接口访问地址: http://api.xxx.com/store/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		$query = StoreModel::find()->select('store_id,store_name,tel,credit_value,praise_rate,stype,sgrade,state,close_reason,apply_remark,add_time,certification,store_banner,store_logo,im_qq,longitude,latitude,address,region_id')->where(['store_id' => $post->store_id]);
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}
		if (!empty($record['certification'])) {
			$record['certification'] = explode(',', $record['certification']);
		}
		$record = $this->formatImagesUrl($record);
		$record['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);

		// 店铺在售商品总数
		$record['goods_count'] = GoodsModel::getCountOfStore($post->store_id);

		// 店铺是否被当前访客收藏
		$record['becollected'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $post->store_id, 'userid' => Yii::$app->user->id])->exists();

		// 店铺被收藏数
		$record['collects'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $post->store_id])->count();

		// 店铺所在地省市区地址信息
		$this->params = array_merge($record, (array) RegionModel::getArrayRegion($record['region_id']));

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 插入店铺信息
	 * @api 接口访问地址: http://api.xxx.com/store/add
	 */
	public function actionAdd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id', 'cate_id', 'sgrade']);

		$model = new \frontend\models\ApplyForm(['store_id' => Yii::$app->user->id]);
		if(!($store = $model->save($post, true))) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}
		foreach($store as $key => $value) {
			if(!in_array($key, ['apply_remark', 'state', 'store_id'])) {
				unset($store->$key);
			}
		}

		return $respond->output(true, null, ArrayHelper::toArray($store));
	}

	/**
	 * 更新店铺信息
	 * @api 接口访问地址: http://api.xxx.com/store/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		if (!($model = StoreModel::find()->where(['store_id' => Yii::$app->user->id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}
		if($post->region_id) {
			$model->region_name = implode(' ', RegionModel::getArrayRegion($post->region_id));
		}
		$fields = ['tel', 'im_qq', 'latitude', 'longitude', 'region_id', 'address'];
		foreach($fields as $key => $value) {
			if(isset($post->$value)) {
				$model->$value = $post->$value;
			}
		}

		if ($post->store_logo) {
			$model->store_logo = $this->getFileSavePath($post->store_logo);
		}
		if($post->store_name) {
			if(StoreModel::find()->where(['store_name' => $post->store_name])->exists()){
				return $respond->output(Respond::PARAMS_INVALID, Language::get('store_name_existed'));
			}
			$model->store_name = $post->store_name;
		}
		if(!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 删除店铺信息
	 * @api 接口访问地址: http://api.xxx.com/store/delete
	 */
	public function actionDelete()
	{

	}

	/**
	 * 获取店铺轮播图
	 * @api 接口访问地址: http://api.xxx.com/store/swiper
	 */
	public function actionSwiper()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		$query = StoreModel::find()->select('swiper')->where(['store_id' => $post->store_id]);
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		if($record['swiper']) {
			$record['swiper']  = (array)json_decode($record['swiper'], true);
			foreach ($record['swiper'] as $key => $value) {
				$this->params[$key]['url'] = Formatter::path($value['url']);
			}
		}

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取店铺动态评分
	 * @api 接口访问地址: http://api.xxx.com/store/dynamiceval
	 */
	public function actionDynamiceval()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
		$this->params = StoreModel::dynamicEvaluation($post->store_id);

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取店铺主体信息（该信息为隐私数据，只允许获取自己店铺的）
	 * @api 接口访问地址: http://api.xxx.com/store/privacy
	 */
	public function actionPrivacy()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$fields = ['identity_front','identity_back','business_license'];
		$record = StoreModel::find()->select(implode(',', array_merge($fields, ['owner_name','identity_card'])))->where(['store_id' => Yii::$app->user->id])->asArray()->one();
		if($record) {
			foreach($fields as $field) {
				$record[$field] = Formatter::path($record[$field]);
			}
		}
		return $respond->output(true, null, $record);
	}

	/**
	 * 店铺收藏
	 * @api 接口访问地址: http://api.xxx.com/store/collect
	 */
	public function actionCollect()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 验证店铺是否存在
		if (!isset($post->store_id) || !StoreModel::find()->where(['store_id' => $post->store_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		// 如果是取消收藏
		if (isset($post->remove) && ($post->remove === true)) {
			if (!CollectModel::deleteAll(['and', ['item_id' => $post->store_id], ['type' => 'store', 'userid' => Yii::$app->user->id]])) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_store_fail'));
			}
		} else {
			if (!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'store', 'item_id' => $post->store_id])->one())) {
				$model = new CollectModel();
			}
			$model->userid = Yii::$app->user->id;
			$model->type = 'store';
			$model->item_id = $post->store_id;
			$model->add_time = Timezone::gmtime();
			if (!$model->save()) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_store_fail'));
			}
		}

		return $respond->output(true, null, ['store_id' => $post->store_id]);
	}

	/**
	 * 获取店铺等级列表
	 * @api 接口访问地址: http://api.xxx.com/store/grades
	 */
	public function actionGrades()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = SgradeModel::find()->select('grade_id,grade_name,goods_limit,space_limit,charge,need_confirm,description')->orderBy(['sort_order' => SORT_ASC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取单条店铺等级信息
	 * @api 接口访问地址: http://api.xxx.com/store/grade
	 */
	public function actionGrade()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['grade_id']);

		$record = SgradeModel::find()->select('grade_id,grade_name,goods_limit,space_limit,charge,need_confirm,description')
			->where(['grade_id' => $post->grade_id])->asArray()->one();
		if (!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_sgrade'));
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取指定店铺的满优惠信息
	 * @api 接口访问地址: http://api.xxx.com/store/fullprefer
	 */
    public function actionFullprefer()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
	
		// 店铺满优惠
		$fullpreferTool = Promotool::getInstance('fullprefer')->build(['store_id' => $post->store_id]);
		
		$result = array();
		if($fullpreferTool->checkAvailable(false)){
			$fullprefer = $fullpreferTool->getInfo();
			if(isset($fullprefer['status']) && $fullprefer['status']) {
				$result['amount'] = $fullprefer['rules']['amount'];
				if($fullprefer['rules']['type'] == 'discount') {
					$result['decrease'] = $fullprefer['rules']['amount'] * ((10-$fullprefer['rules']['discount'])/10);
				} else {
					$result['decrease'] = $fullprefer['rules']['decrease'];
				}
			}
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取指定的店铺的满包邮信息
	 * @api 接口访问地址: http://api.xxx.com/store/fullfree
	 */
    public function actionFullfree()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
	
		// 店铺包邮
		$fullfreeTool = Promotool::getInstance('fullfree')->build(['store_id' => $post->store_id]);

		$result = array();
		if($fullfreeTool->checkAvailable(false)) {
			$fullfree = $fullfreeTool->getInfo();
			if(isset($fullfree['status']) && $fullfree['status']) {
				if($fullfree['rules']['amount'] > 0 || $fullfree['rules']['quantity'] > 0) {
					$result = $fullfree['rules'];
				}
			}
		}

		return $respond->output(true, null, $result);
	}

	private function getConditions($query, $post)
	{
		// 关键词搜索
		if (isset($post->keyword) && $post->keyword) {
			$query->andWhere(['like', 's.store_name', $post->keyword]);
		}

		// 店铺分类
		if (isset($post->cate_id) && $post->cate_id) {
			$allId = ScategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cs.cate_id', $allId]);
		}
		// 主体类型
		if(in_array($post->stype, ['company', 'personal'])) {
			$query->andWhere(['s.stype' => $post->stype]);
		}
		// 店铺等级
		if (isset($post->sgrade) && $post->sgrade) {
			$query->andWhere(['s.sgrade' => $post->sgrade]);
		}
		// 好评率
		if ($post->praise_rate) {
			$query->andWhere(['>=', 'praise_rate', $post->praise_rate]);
		}
		// 地区
		if (isset($post->region_id) && $post->region_id) {
			$allId = RegionModel::getDescendantIds($post->region_id);
			$query->andWhere(['in', 'region_id', $allId]);
		}
		// 排序
		if (isset($post->orderby) && in_array($post->orderby, ['credit_value|desc', 'praise_rate|desc', 'add_time|desc'])) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
		} else $query->orderBy(['s.store_id' => SORT_DESC]);

		return $query;
	}

	private function formatImagesUrl($record)
	{
		$fields = ['store_logo', 'store_banner', 'credit_value'];
		foreach($fields as $field) {
			if(isset($record[$field])) {
				if($field == 'credit_value') {
					$record['credit_image'] = Resource::getThemeAssetsUrl(['file' => 'images/credit/' . StoreModel::computeCredit($record[$field]), 'baseUrl' => Basewind::homeUrl()]);
				} else {
					$record[$field] = Formatter::path($record[$field], $field == 'store_logo' ? 'store' : '');
				}
			}
		}
		return $record;
	}

	/**
	 * 如果是本地存储，存相对地址，如果是云存储，存完整地址
	 */
	private function getFileSavePath($image = '')
	{
		if(stripos($image, Def::fileSaveUrl()) !== false) {
			return str_replace(Def::fileSaveUrl() . '/', '', $image);
		} 
		return $image;
	}
}
