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

use common\models\BrandModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id BrandController.php 2018.8.9 $
 * @author mosir
 */

class BrandController extends \common\controllers\BaseAdminController
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
			
			$this->params['_foot_tags'] = Resource::import('inline_edit.js');
			$this->params['page'] = Page::seo(['title' => Language::get('brand_list')]);
			return $this->render('../brand.index.html', $this->params);
		}
		else
		{
			$query = BrandModel::find()->select('brand_id,brand_name,brand_logo,cate_id,tag,letter,sort_order')->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC]);
			$query = $this->getConditions($post, $query);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$category = GcategoryModel::find()->select('cate_name')->where(['cate_id' => $value['cate_id']])->scalar();
				$list[$key]['cate_name'] = $category ? $category : '';
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['brand'] = ['recommended' => 0, 'sort_order' => 255, 'if_show' => 1];
			
			// 取得一级商品分类
			$this->params['gcategories'] = GcategoryModel::getOptions(0, 0);
			
			$this->params['page'] = Page::seo(['title' => Language::get('brand_add')]);
			return $this->render('../brand.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'recommended', 'cate_id']);
			
			$model = new \backend\models\BrandForm();
			if(!($brand = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['brand/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($brand = BrandModel::find()->where(['brand_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_brand'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['brand'] = ArrayHelper::toArray($brand);
			
			// 取得一级商品分类
			$this->params['gcategories'] = GcategoryModel::getOptions(0, 0);
			
			$this->params['page'] = Page::seo(['title' => Language::get('brand_edit')]);
			return $this->render('../brand.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order', 'recommended', 'cate_id']);

			$model = new \backend\models\BrandForm(['brand_id' => $id]);
			if(!($brand = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['brand/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		if(is_array($post->id) && !empty($post->id)) {
			BrandModel::deleteAll(['in', 'brand_id', $post->id]);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order', 'recommended']);
		if(in_array($post->column, ['brand_name', 'if_show', 'sort_order', 'recommended', 'tag', 'letter'])) {
			$model = new \backend\models\BrandForm(['brand_id' => $post->id]);
			$query = BrandModel::findOne($post->id);
			$query->{$post->column} = $post->column == 'letter' ? strtoupper($post->value) : $post->value;
			if(!($brand = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = BrandModel::find()->select('brand_id,brand_name,brand_logo,if_show,recommended,tag,letter')->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'brand_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\BrandExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['brand_name', 'tag', 'letter'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->brand_name) {
			$query->andWhere(['like', 'brand_name', $post->brand_name]);
		}
		if($post->tag) {
			$query->andWhere(['like', 'tag', $post->tag]);
		}
		if($post->letter) {
			$query->andWhere(['like', 'letter', $post->letter]);
		}

		return $query;
	}
}
