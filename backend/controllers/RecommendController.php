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

use common\models\RecommendModel;
use common\models\RecommendGoodsModel;
use common\models\GoodsModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id RecommendController.php 2018.8.14 $
 * @author mosir
 */

class RecommendController extends \common\controllers\BaseAdminController
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
			$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.ui/jquery.ui.js,dialog/dialog.js,inline_edit.js']);
		
			$this->params['page'] = Page::seo(['title' => Language::get('recommend_list')]);
			return $this->render('../recommend.index.html', $this->params);
		}
		else
		{
			$query = RecommendModel::find()->select('recom_id,recom_name');
			$query = $this->getConditions($post, $query)->orderBy(['recom_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['goods_count'] = RecommendGoodsModel::find()->select('id')->where(['recom_id' => $value['recom_id']])->count();
			}
			
			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['page'] = Page::seo(['title' => Language::get('recmmend_add')]);
			return $this->render('../recommend.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \backend\models\RecommendForm();
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('add_ok'));
		}
	}

	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($recommend = RecommendModel::find()->where(['recom_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('recommend_empty'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['recommend'] = $recommend;
			$this->params['page'] = Page::seo(['title' => Language::get('recmmend_edit')]);
			return $this->render('../recommend.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$model = new \backend\models\RecommendForm(['recom_id' => $id]);
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('edit_ok'));
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_recommend'));
		}
		$model = new \backend\models\RecommendDeleteForm(['recom_id' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['recommend/index']);
	}
	
	public function actionGoods()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page', 'id']);
		if(!$post->id || !($recommend = RecommendModel::find()->where(['recom_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_recommend'));
		}
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['page'] = Page::seo(['title' => Language::get('recommend_goods')]);
			return $this->render('../recommend.goods.html', $this->params);
		}
		else
		{
			$allId = RecommendGoodsModel::find()->select('goods_id')->where(['recom_id' => $post->id])->column();
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.price,g.store_id')
				->where(['in', 'g.goods_id', $allId])
				->orderBy(['goods_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['recom_name'] = $recommend['recom_name'];
				$list[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $value['store_id']])->scalar();
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	/* 取消推荐 */
    public function actionCancel()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_goods'));
		}
		$model = new \backend\models\RecommendCancelForm(['goods_id' => $post->id]);
		if(!$model->cancel($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('cancel_ok'), ['recommend/index']);
    }

	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(in_array($post->column, ['recom_name'])) {
			$model = new \backend\models\RecommendForm(['recom_id' => $post->id]);
			$query = RecommendModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!($brand = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['recom_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->recom_name) {
			$query->andWhere(['like', 'recom_name', $post->recom_name]);
		}
		return $query;
	}
}
