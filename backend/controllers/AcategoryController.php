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

use common\models\AcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id AcategoryController.php 2018.8.22 $
 * @author mosir
 */

class AcategoryController extends \common\controllers\BaseAdminController
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
		$acategories = AcategoryModel::getList(0, 0, false);
		foreach ($acategories as $key => $val)
        {
            $acategories[$key]['switchs'] = 0;
			if(AcategoryModel::find()->where(['parent_id' => $val['cate_id']])->exists()) {
				$acategories[$key]['switchs'] = 1;
            }
        }
		$this->params['acategories'] = $acategories;
		
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css,dialog/dialog.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.ui/jquery.ui.js,dialog/dialog.js,treetable/atree.js,inline_edit.js']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('acategory')]);
		return $this->render('../acategory.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['acategory'] = ['parent_id' => intval(Yii::$app->request->get('pid')), 'sort_order' => 255];
			$this->params['parents'] = AcategoryModel::getOptions();
			
			$this->params['page'] = Page::seo(['title' => Language::get('acategory_add')]);
			return $this->render('../acategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\AcategoryForm();
			if(!($acategory = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('add_ok'));		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($acategory = AcategoryModel::find()->where(['store_id' => 0, 'cate_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_acategory'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['acategory'] = ArrayHelper::toArray($acategory);
			$this->params['parents'] = AcategoryModel::getOptions(0, -1, $id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('acategory_edit')]);
			return $this->render('../acategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\AcategoryForm(['cate_id' => $id]);
			if(!($acategory = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('edit_ok'));		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		foreach($post->id as $id) {
			if($id == 1) continue; // 不允许删除系统分类
			if($id && ($allId = AcategoryModel::getDescendantIds($id))) {
				AcategoryModel::deleteAll(['and', ['store_id' => 0], ['in', 'cate_id', $allId]]);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 异步取所有下级 */
   	public function actionChild()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$post->id) {
			return Message::warning(false);
		}
		
		$acategories = AcategoryModel::getList($post->id, 0, false);
		foreach ($acategories as $key => $val)
        {
            $acategories[$key]['switchs'] = 0;
			if(AcategoryModel::find()->where(['parent_id' => $val['cate_id']])->exists()) {
				$acategories[$key]['switchs'] = 1;
            }
			
			// 暂时不限制级别
			$acategories[$key]['add_child'] = 1;
        }
		return Message::result(array_values($acategories));
    }
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order']);
		if(in_array($post->column, ['cate_name', 'if_show', 'sort_order'])) {
			if($post->column == 'if_show') {
				$allId = $post->id ? AcategoryModel::getDescendantIds($post->id) : array();
				if(!AcategoryModel::updateAll(['if_show' => $post->value], ['and', ['store_id' => 0], ['in', 'cate_id', $allId]])) {
					return Message::warning(Language::get('edit_fail'));
				}
			} 
			else 
			{
				$model = new \backend\models\AcategoryForm(['cate_id' => $post->id]);
				$query = AcategoryModel::findOne($post->id);
				$query->{$post->column} = $post->value;
				if(!($acategory = $model->save($query, true))) {
					return Message::warning($model->errors);
				}
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
}
