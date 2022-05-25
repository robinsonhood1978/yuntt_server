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

use common\models\UserModel;
use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsImageModel;
use common\models\OrderGoodsModel;
use common\models\GoodsQaModel;
use common\models\GoodsStatisticsModel;
use common\models\GcategoryModel;
use common\models\CollectModel;
use common\models\UploadedFileModel;
use common\models\GuideshopModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;
use common\library\Page;
use common\library\Def;
use common\library\Weixin;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id GoodsController.php 2018.12.25 $
 * @author yxyc
 */

class GoodsController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取商品列表
	 * @api 接口访问地址: http://api.xxx.com/goods/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'store_id', 'if_show', 'recommended', 'page', 'page_size']);
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.cate_id,g.brand,g.add_time,g.default_spec,g.spec_qty,g.default_image,g.recommended,g.price,g.mkprice,s.store_id,s.store_name,gs.stock,gst.views,gst.collects,gst.sales,gst.comments,gc.cate_name')
			->joinWith('store s', false)->joinWith('goodsStatistics gst', false)
			->joinWith('goodsDefaultSpec gs', false)
			->joinWith('gcategory gc', false)
			->where(['g.closed' => 0]);
		
		// 在售商品
		if(!isset($post->if_show) || $post->if_show) {
			$query->andWhere(['g.if_show' => 1, 's.state' => 1]);
		}
		// 待上架商品
		else $query->andWhere(['g.if_show' => 0]);

		if(isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'g.goods_id', explode(',', $post->items)]);
		}
			
		$model = new \apiserver\models\GoodsForm();
		$query = $model->getBasicConditions($post, $query);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		$promotool = Promotool::getInstance()->build();
		foreach($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);

			// 读取商品促销价格
			if(($promotion = $promotool->getItemProInfo($value['goods_id'], $value['default_spec']))) {
				$list[$key]['promotion'] = $promotion;
			}
		}
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		return $respond->output(true, Language::get('goods_list'), $this->params);
	}
	
	/**
	 * 搜索商品列表
	 * @api 接口访问地址: http://api.xxx.com/goods/search
	 */
    public function actionSearch()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'store_id', 'if_show', 'closed', 'region_id', 'recommended', 'page', 'page_size']);
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.cate_id,g.brand,g.if_show,g.closed,g.add_time,g.default_spec,g.default_image,g.recommended,g.price,g.mkprice,g.dt_id,s.store_id,s.store_name,gs.stock,gst.views,gst.collects,gst.sales,gst.comments,gc.cate_name')
			->joinWith('goodsStatistics gst', false)->where(['s.state' => 1])
			->joinWith('gcategory gc', false);
		
		$model = new \apiserver\models\GoodsForm();
		$query = $model->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);

			// 商品从图(不包含主图)
			$otherImages = GoodsImageModel::find()->select('thumbnail')->where(['goods_id' => $value['goods_id']])->andWhere(['!=', 'sort_order', 1])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->column();
			foreach($otherImages as $k => $image) {
				$otherImages[$k] = Formatter::path($image);
			}
			$list[$key]['other_image'] = $otherImages;
		}
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		$this->params = array_merge($this->params, $model->getSelectors($post), $model->getFilters($post));
		
		return $respond->output(true, Language::get('goods_list'), $this->params);
	}
	
	/**
	 * 商品推送/猜你喜欢数据（或感兴趣的商品）
	 * @api 接口访问地址: http://api.xxx.com/goods/push
	 * @var int $limit 需要获取的数量
	 * @var string|int $cate_id  用逗号隔开的多个id或一个id，如：cate_id=1 或 cate_id=1,2,3
	 * @var int $store_id
	 */
	public function actionPush()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'limit']);

		$list = $allId = [];

		// 必须指定分类
		if(!isset($post->cate_id) || !$post->cate_id) {
			return $respond->output(true);
		}

		$array = explode(',', $post->cate_id);
		foreach($array as $key => $value) {
			if(($value = intval(trim($value))) && ($descendant = GcategoryModel::getDescendantIds($value))) {
				$allId = array_merge($allId, $descendant);
			}
		}
		if(!($allId = array_unique($allId))) {
			return $respond->output(true);
		}
		
		// 最多获取100个数据
		$query = GoodsModel::find()->select('goods_id,goods_name,default_image,price')->where(['in', 'cate_id', $allId])->andWhere(['if_show' => 1, 'closed' => 0])->limit(100);
		if(isset($post->store_id) && $post->store_id > 0) {
			$query->andWhere(['store_id' => $post->store_id]);
		}
		
		// 如果没有限制数量，默认从总数中随机取10个
		if(!isset($post->limit) || $post->limit <= 0) {
			$post->limit = 10;
		}
		if($query->count() <= $post->limit) {
			$list = $query->asArray()->all();
		}
		else {
			
			// 取得随机数
			$array = range(0, $query->count() - 1);
			shuffle($array);
			$array = array_slice($array, -$post->limit);
			
			$all = $query->asArray()->all();
			foreach($array as $key => $value) {
				$list[$value] = $all[$value];
			}
			array_values($list);
		}

		foreach($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
		}

		return $respond->output(true, null,  ['list' => $list]);
	}
	
	/**
	 * 获取商品单条信息
	 * @api 接口访问地址: http://api.xxx.com/goods/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		$model = new \apiserver\models\GoodsForm();
		$record = $model->formData($post);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品描述信息
	 * @api 接口访问地址: http://api.xxx.com/goods/description
	 */
    public function actionDescription()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		$record = GoodsModel::find()->select('description')->where(['goods_id' => $post->goods_id])->asArray()->one();
		if($record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_goods'));
		}
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 插入商品信息
	 * @api 接口访问地址: http://api.xxx.com/goods/add
	 */
    public function actionAdd()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['recommended', 'if_show', 'cate_id', 'spec_qty', 'dt_id']);

		$model = new \frontend\models\GoodsForm(['store_id' => Yii::$app->user->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		
		return $respond->output(true);
	}
	
	/**
	 * 更新商品信息
	 * @api 接口访问地址: http://api.xxx.com/goods/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'recommended', 'if_show', 'cate_id', 'spec_qty', 'dt_id']);

		$model = new \frontend\models\GoodsForm(['store_id' => Yii::$app->user->id, 'goods_id' => $post->goods_id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		
		return $respond->output(true);
	}
	
	/**
	 * 删除商品信息
	 * @api 接口访问地址: http://api.xxx.com/goods/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		if(!$post->goods_id || !(GoodsModel::find()->where(['goods_id' => $post->goods_id, 'store_id' => Yii::$app->user->id])->exists())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('on_such_goods'));
		}

		// 演示店铺的商品不允许删除，交付给客户的源码去掉这个限制
		if(Yii::$app->user->id != 2) {
			$model = new \frontend\models\GoodsDeleteForm(['goods_id' => $post->goods_id, 'store_id' => Yii::$app->user->id]);
			$model->delete($post);
		}

		return $respond->output(true);
	}
	
	/**
	 * 获取商品规格列表
	 * @api 接口访问地址: http://api.xxx.com/goods/specs
	 */
	public function actionSpecs()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		$query = GoodsSpecModel::find()->alias('gs')->select('gs.goods_id,gs.spec_id,gs.spec_1,gs.spec_2,gs.price,gs.mkprice,gs.stock,gs.spec_image as image,gst.sales,g.goods_name,g.default_image,g.spec_qty,g.spec_name_1,g.spec_name_2')->joinWith('goods g', false)->joinWith('goodsStatistics gst', false)->where(['gs.goods_id' => $post->goods_id])->orderBy(['sort_order' => SORT_ASC, 'gs.spec_id' => SORT_ASC]);
		
		// 如果筛选规格一
		if(isset($post->spec_1) && $post->spec_1) {
			$query->andWhere(['spec_1' => $post->spec_1]);
		}
		// 如果筛选规格二
		if(isset($post->spec_2) && $post->spec_2) {
			$query->andWhere(['spec_2' => $post->spec_2]);
		}
		$list = $query->asArray()->all();

		$promotool = Promotool::getInstance()->build();
		foreach($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['image'] = Formatter::path($value['image']);

			// 读取商品促销价格
			if(($promotion = $promotool->getItemProInfo($value['goods_id'], $value['spec_id']))) {
				$list[$key]['promotion'] = $promotion;
			}
		}
		$this->params['list'] = $list;
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品规格单条信息
	 * @api 接口访问地址: http://api.xxx.com/goods/spec
	 */
	public function actionSpec()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id']);
		
		$record = GoodsSpecModel::find()->select('spec_id,goods_id,spec_1,spec_2,price,mkprice,stock,spec_image as image')->where(['spec_id' => $post->spec_id])->asArray()->one();
		if(!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_spec_invalid'));
		}
		$record['image'] = Formatter::path($record['image']);
		
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 获取商品价格单条信息（考虑促销价格）
	 * @api 接口访问地址: http://api.xxx.com/goods/price
	 */
	public function actionPrice()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id']);
		
		$record = GoodsSpecModel::find()->select('goods_id,spec_id,price,mkprice')->where(['spec_id' => $post->spec_id])->asArray()->one();
		if(!($record)) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_goods'));
		}
		
		// 读取商品促销价格
		$promotool = Promotool::getInstance()->build();
		$promotion = $promotool->getItemProInfo($record['goods_id'], $post->spec_id);
		$this->params = array_merge($record, ['promotion' => $promotion]);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品上下架状态信息
	 * @api 接口访问地址: http://api.xxx.com/goods/shelfstate
	 */
	public function actionShelfstate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		$query = GoodsModel::find()->select('if_show,closed')->where(['goods_id' => $post->goods_id])->one();
		if(!$query) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		$record = ['goods_id' => $post->goods_id, 'state' => ($query->if_show && !$query->closed) ? 1 : 0];
		
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 获取商品相册图片列表
	 * @api 接口访问地址: http://api.xxx.com/goods/images
	 */
	public function actionImages()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		$list = GoodsImageModel::find()->select('goods_id,image_id,image_url,thumbnail')->where(['goods_id' => $post->goods_id])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['image_url'] = Formatter::path($value['image_url']);
			$list[$key]['thumbnail'] = Formatter::path($value['thumbnail']);
		}
		$this->params['list'] = $list;
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品相册单张图片信息
	 * @api 接口访问地址: http://api.xxx.com/goods/image
	 */
	public function actionImage()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['image_id']);
		
		$record = GoodsImageModel::find()->where(['goods_id' => $post->goods_id])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->asArray()->one();
		$record['image_url'] = Formatter::path($record['image_url']);
		$record['thumbnail'] = Formatter::path($record['thumbnail']);
		
		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品描述图片列表
	 * @api 接口访问地址: http://api.xxx.com/goods/descimages
	 */
	public function actionDescimages()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$uploadedfiles = UploadedFileModel::find()->alias('f')->select('f.file_id,f.file_path,gi.goods_id')
			->joinWith('goodsImage gi', false)
			->where(['store_id' => Yii::$app->user->id, 'item_id' => $post->goods_id, 'belong' => Def::BELONG_GOODS])
			->orderBy(['sort_order' => SORT_ASC, 'file_id' => SORT_ASC])
			->asArray()->all();

		$list = [];
		if($uploadedfiles) {
			foreach($uploadedfiles as $key => $file) {
				if(!$file['goods_id']) {
					$list[] = Formatter::path($file['file_path']);
				}
			}
		}

		return $respond->output(true, null, ['list' => $list]);
	}
	
	/**
	 * 获取商品属性列表信息
	 * @api 接口访问地址: http://api.xxx.com/goods/attributes
	 */
	public function actionAttributes()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		$model = new \apiserver\models\GoodsForm();
		if(!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		$this->params['list'] = $model->getGoodProps($post->goods_id);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品物流运费信息
	 * @api 接口访问地址: http://api.xxx.com/goods/logistics
	 * @return 返回多个运费方式(express/ems/post)的运费情况
	 */
	public function actionLogistics()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'city_id']);
		
		if(!isset($post->goods_id) || !($query = GoodsModel::find()->select('dt_id,store_id')->where(['goods_id' => $post->goods_id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		
		$model = new \apiserver\models\GoodsForm();
		$record = $model->getLogistics($query->dt_id, $post->city_id, $query->store_id);
		
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 获取商品销售记录列表
	 * @api 接口访问地址: http://api.xxx.com/goods/salelogs
	 */
	public function actionSalelogs()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'page', 'page_size']);
		
		if(!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		
		$query = OrderGoodsModel::find()->alias('og')->select('og.rec_id,og.spec_id,og.goods_id,og.goods_name,og.specification,og.price,og.quantity,og.evaluation,og.evaluation_status,o.buyer_id,o.buyer_name,o.add_time,o.anonymous')->joinWith('order o', false)->where(['goods_id' => $post->goods_id, 'o.status' => Def::ORDER_FINISHED])->orderBy(['add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
		}
		
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品评价记录列表
	 * @api 接口访问地址: http://api.xxx.com/goods/comments
	 * @var boolean $commented 是否只读有评论内容的记录
	 */
	public function actionComments()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'level', 'page', 'page_size']);
		
		if(!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		
		$query = OrderGoodsModel::find()->alias('og')->select('og.rec_id,og.spec_id,og.goods_id,og.goods_name,og.specification,og.evaluation,og.comment,og.reply_comment,og.reply_time,o.buyer_id,o.buyer_name,o.evaluation_time')->joinWith('order o', false)->where(['goods_id' => $post->goods_id, 'o.evaluation_status' => 1])->orderBy(['o.evaluation_time' => SORT_DESC]);
		
		// 只获取有评价内容的记录
		if(isset($post->commented) && $post->commented === true) {
			$query->andWhere(['>', 'comment', '']);
		}
		
		// 数据库字段记录的是5分制，3分为中评
		if(isset($post->level)) {
			if($post->level == 1) $query->andWhere(['<', 'evaluation', 3]);
			if($post->level == 2) $query->andWhere(['=', 'evaluation', 3]);
			if($post->level == 3) $query->andWhere(['>', 'evaluation', 3]);
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['reply_time'] = Timezone::localDate('Y-m-d H:i:s', $value['reply_time']);
			$list[$key]['evaluation_time'] = Timezone::localDate('Y-m-d H:i:s', $value['evaluation_time']);
			
			$portrait = UserModel::find()->select('portrait')->where(['userid' => $value['buyer_id']])->scalar();
			$list[$key]['buyer_portrait'] = Formatter::path($portrait, 'portrait');
		}
		
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		
		// 评价统计数据
		$this->params = array_merge($this->params, GoodsStatisticsModel::getCommectStatistics($post->goods_id));
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取商品问答列表
	 * @api 接口访问地址: http://api.xxx.com/goods/qas
	 * @var boolean $replied 是否只读有回复内容的记录
	 */
	public function actionQas()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'page', 'page_size']);
		
		if(!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
	
		$query = GoodsQaModel::find()->alias('ga')->select('ga.ques_id,ga.item_id,ga.item_name,ga.question_content,ga.reply_content,ga.time_post,ga.time_reply,ga.if_new,u.userid,u.username,u.nickname')->joinWith('user u', false)->where(['item_id' => $post->goods_id, 'type' => 'goods'])->orderBy(['time_post' => SORT_DESC]);
		
		// 只取有回复内容的问答
		if(isset($post->replied) && $post->replied === true) {
			$query->andWhere(['>', 'reply_content', '']);
		}
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['time_post'] = Timezone::localDate('Y-m-d H:i:s', $value['time_post']);
			$list[$key]['time_reply'] = Timezone::localDate('Y-m-d H:i:s', $value['time_reply']);
		}
		
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 插入商品问答信息
	 * @api 接口访问地址: http://api.xxx.com/goods/qaadd
	 * @var boolean $anonymous 是否匿名咨询
	 */
	public function actionQaadd()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		
		if(!isset($post->goods_id) || !($goods = GoodsModel::find()->select('store_id,goods_name')->where(['goods_id' => $post->goods_id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		
		if($goods->store_id == Yii::$app->user->id) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('not_question_self'));
		}
		
		if(!isset($post->content) || empty($post->content)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('question_content_invalid'));
		}

		$model = new GoodsQaModel();
		$model->question_content = $post->content;
		$model->type = 'goods';
		$model->item_id = $post->goods_id;
		$model->item_name = addslashes($goods->goods_name);
		$model->store_id = $goods->store_id;
		//$model->email = isset($post->email) ? $post->email : '';
		$model->userid = (isset($post->anonymous) && $post->anonymous === true) ? 0 : Yii::$app->user->id;
		$model->time_post = Timezone::gmtime();
		if(!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, Language::get('question_content_fail'));
		}
		return $respond->output(true, null, ['ques_id' => $model->ques_id]);
	}

	/**
	 * 商品收藏
	 * @api 接口访问地址: http://api.xxx.com/goods/collect
	 * @var int $goods_id
	 * @var boolean $remove 取消收藏
	 */
    public function actionCollect()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		// 验证商品是否存在
		if(!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		// 如果是取消收藏
		if(isset($post->remove) && ($post->remove === true)) {
			if(!CollectModel::deleteAll(['and', ['item_id' => $post->goods_id], ['type' => 'goods', 'userid' => Yii::$app->user->id]])) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_goods_fail'));
			}
		}
		else 
		{
			if(!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'goods', 'item_id' => $post->goods_id])->one())) {
				$model = new CollectModel();
			}
			$model->userid = Yii::$app->user->id;
			$model->type = 'goods';
			$model->item_id = $post->goods_id;
			$model->add_time = Timezone::gmtime();
			if(!$model->save()) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_goods_fail'));
			}
			// 更新被收藏总次数
			GoodsStatisticsModel::updateStatistics($post->goods_id, 'collects');
		}

		return $respond->output(true, null, ['goods_id' => $post->goods_id]);
	}

	/**
	 * 批量获取指定商品列表 （即将废弃，请改用goods/list接口）
	 * @api 接口访问地址: http://api.xxx.com/goods/query
	 * @var string|int $goods_id
	 * @example goods_id=1 或 goods_id=1,2,3
	 * @var string|int $spec_id
	 * @example spec_id=1 或 spec_id=1,2,3
	 */
	public function actionQuery()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if((!isset($post->goods_id) || !$post->goods_id) && (!isset($post->spec_id) || !$post->spec_id)) {
			return $respond->output(true);
		}
		$allId = explode(',', $post->goods_id ? $post->goods_id : $post->spec_id);
		foreach($allId as $key => $value) {
			$allId[$key] = intval(trim($value));
		}
		
		// 通过商品ID查数据
		if($post->goods_id) {
			$query = GoodsModel::find()->alias('g')->select('g.default_spec as spec_id')->andWhere(['in', 'g.goods_id', $allId])->indexBy('goods_id');
		}
		// 通过商品SKU查数据
		else {
			$query = GoodsSpecModel::find()->alias('gs')->select('gs.spec_id')->joinWith('goods g', false)->andWhere(['in', 'gs.spec_id', $allId])->indexBy('spec_id');
		}
		$list = $query->addSelect('g.goods_id,g.goods_name,g.default_image,g.price,gst.sales')->joinWith('goodsStatistics gst', false)->asArray()->all();
		
		foreach($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
		}

		$all = [];
		foreach($allId as $id) {
			if(isset($list[$id])) {
				$all[] = $list[$id];
			}
		}

		return $respond->output(true, null, ['list' => $all]);
	}

	/**
	 * 获取商品推广海报
	 * @api 接口访问地址: http://api.xxx.com/goods/qrcode
	 */
	public function actionQrcode()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'shopid']);

		// 验证商品是否存在
		if(!isset($post->goods_id) || !($goods = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.default_spec as spec_id,g.price,s.store_id,s.store_name,s.store_logo')->joinWith('store s', false)->where(['goods_id' => $post->goods_id])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		$path = UploadedFileModel::getSavePath($goods['store_id'], Def::BELONG_POSTER);
		
		$wxacode = $path . "/wxacode".md5($post->page).".png";
		if(!file_exists($wxacode)) {
			$response = Weixin::getInstance(null, 0, 'applet')->getWxaCode(['path' => $post->page, 'width' => 280], $wxacode);
			if($response === false) {
				return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
			}
		}
		$goods['default_image'] = Formatter::path($goods['default_image'], 'goods');

		// 读取促销价格
		if(($promotion = Promotool::getInstance()->build()->getItemProInfo($goods['goods_id'], $goods['spec_id']))) {
			$goods['price'] = $promotion['price'];
		}
		
		// 如果是社区团购的商品海报
		if(($guideshop = $this->getGuideshop($post))) {
			list($name, $logo, $background) = $guideshop;
		} else {
			list($name, $logo, $background) = [$goods['store_name'], Formatter::path($goods['store_logo'], 'store'),  Yii::getAlias('@frontend'). '/web/static/images/goodsQrcode.jpg'];
		}
		
		// 生成海报
		$config = [
			'text' => [['text' => $name, 'left' => 300, 'top' => 400, 'fontSize' => 40], ['text' => mb_substr($goods['goods_name'], 0, 14, Yii::$app->charset), 'left' => 80, 'top' => 1440, 'fontSize' => 30], ['text' => mb_substr($goods['goods_name'], 14, 14, Yii::$app->charset), 'left' => 80, 'top' => 1500, 'fontSize' => 30], ['text' => '￥'.$goods['price'], 'left' => 80, 'top' => 1640, 'fontSize' => 50, 'fontColor' => '255,0,0']],
			'image' => [['url' => $logo, 'left' => 100, 'top' => 310, 'width' => 160, 'height' => 160], ['url' => $wxacode, 'left' => 670, 'top' => 1370, 'width' => 250, 'height' => 250], ['url' => $goods['default_image'], 'left' => 100, 'top' => 540, 'width' => 800, 'height' => 800]],
			'background' => $background
		];
		
		$qrcode = Page::createPoster($config, $path . "/poster".md5($post->page).".png");
		$result['poster'] = str_replace(Yii::getAlias('@frontend'). '/web', Basewind::homeUrl(), $qrcode);

		return $respond->output(true, null, $result);
	}

	private function getGuideshop($post)
	{
		if(!$post->shopid || !($model = GuideshopModel::find()->select('name,banner')->where(['id' => $post->shopid])->one())) {
			return false;
		}
		
		return [$model->name, Formatter::path($model->banner, 'store'), Yii::getAlias('@frontend'). '/web/static/images/guideGoodsQrcode.jpg'];
	}
}