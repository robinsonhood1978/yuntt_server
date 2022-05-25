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

use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id GcategoryController.php 2018.8.8 $
 * @author mosir
 */

class GcategoryController extends \common\controllers\BaseAdminController
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
		$gcategories = GcategoryModel::getList(0, 0, false);
		foreach ($gcategories as $key => $val)
        {
            $gcategories[$key]['switchs'] = 0;
			if(GcategoryModel::find()->where(['parent_id' => $val['cate_id']])->exists()) {
				$gcategories[$key]['switchs'] = 1;
            }
        }
		$this->params['gcategories'] = $gcategories;
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'treetable/gtree.js,inline_edit.js']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('gcategory_list')]);
		return $this->render('../gcategory.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategory'] = ['parent_id' => intval(Yii::$app->request->get('pid')), 'sort_order' => 255];
			$this->params['parents'] = GcategoryModel::getOptions();
			
			$this->params['page'] = Page::seo(['title' => Language::get('gcategory_add')]);
			return $this->render('../gcategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\GcategoryForm();
			if(!($gcategory = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['gcategory/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($gcategory = GcategoryModel::find()->where(['store_id' => 0, 'cate_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_gcategory'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategory'] = ArrayHelper::toArray($gcategory);
			$this->params['parents'] = GcategoryModel::getOptions(0, -1, $id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('gcategory_edit')]);
			return $this->render('../gcategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\GcategoryForm(['cate_id' => $id]);
			if(!($gcategory = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['gcategory/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		foreach($post->id as $id) {
			if($id && ($allId = GcategoryModel::getDescendantIds($id))) {
				GcategoryModel::deleteAll(['and', ['store_id' => 0], ['in', 'cate_id', $allId]]);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionAd()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($gcategory = GcategoryModel::find()->where(['store_id' => 0, 'cate_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_gcategory'));
		}
		if($gcategory->parent_id) {
			return Message::warning(Language::get('ad_valid'));
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategory'] = ArrayHelper::toArray($gcategory);
			$this->params['page'] = Page::seo(['title' => Language::get('gcategory_ad')]);
			return $this->render('../gcategory.ad.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\GcategoryAdForm(['cate_id' => $id]);
			if(!($gcategory = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['gcategory/index']);	
		}
	}
	
	/* 异步取所有下级 */
   	public function actionChild()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'shown']);
		if(!$post->id) {
			return Message::warning(false);
		}
		
		$list = GcategoryModel::getList($post->id, 0, $post->shown ? true : false);
		foreach ($list as $key => $value)
        {
			$query = GcategoryModel::find()->select('cate_id')->where(['parent_id' => $value['cate_id']]);
			if($post->shown) {
				$query->andWhere(['if_show' => 1]);
			}
			if($query->exists()) {
				$list[$key]['switchs'] = 1;
            }
			
			// 允许添加下级标识
			$list[$key]['add_child'] = 1;
        }
		return Message::result(array_values($list));
    }
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order', 'groupid']);
		if(in_array($post->column, ['cate_name', 'if_show', 'sort_order', 'groupid'])) {
			if($post->column == 'if_show') {
				$allId = $post->id ? GcategoryModel::getDescendantIds($post->id) : array();
				if(!GcategoryModel::updateAll(['if_show' => $post->value], ['and', ['store_id' => 0], ['in', 'cate_id', $allId]])) {
					return Message::warning(Language::get('edit_fail'));
				}
			} 
			else 
			{
				$model = new \backend\models\GcategoryForm(['cate_id' => $post->id]);
				$query = GcategoryModel::findOne($post->id);
				$query->{$post->column} = $post->value;
				if(!($gcategory = $model->save($query, true))) {
					return Message::warning($model->errors);
				}
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
}
