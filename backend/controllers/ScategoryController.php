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

use common\models\ScategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id ScategoryController.php 2018.8.16 $
 * @author mosir
 */

class ScategoryController extends \common\controllers\BaseAdminController
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
		$scategories = ScategoryModel::getList(0, false);
		foreach ($scategories as $key => $val)
        {
            $scategories[$key]['switchs'] = 0;
			if(ScategoryModel::find()->where(['parent_id' => $val['cate_id']])->exists()) {
				$scategories[$key]['switchs'] = 1;
            }
        }
		$this->params['scategories'] = $scategories;
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'treetable/stree.js,inline_edit.js']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('scategory')]);
		return $this->render('../scategory.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['scategory'] = ['parent_id' => intval(Yii::$app->request->get('pid')), 'sort_order' => 255];
			$this->params['parents'] = ScategoryModel::getOptions();
			
			$this->params['page'] = Page::seo(['title' => Language::get('scategory_add')]);
			return $this->render('../scategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\ScategoryForm();
			if(!($scategory = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['scategory/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($scategory = ScategoryModel::findOne($id))) {
			return Message::warning(Language::get('no_such_scategory'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['scategory'] = ArrayHelper::toArray($scategory);
			$this->params['parents'] = ScategoryModel::getOptions(-1, $id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('scategory_edit')]);
			return $this->render('../scategory.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\ScategoryForm(['cate_id' => $id]);
			if(!($scategory = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['scategory/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		foreach($post->id as $id) {
			if($id && ($allId = ScategoryModel::getDescendantIds($id))) {
				ScategoryModel::deleteAll(['in', 'cate_id', $allId]);
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
		
		$scategories = ScategoryModel::getList($post->id, false);
		foreach ($scategories as $key => $val)
        {
            $scategories[$key]['switchs'] = 0;
			if(ScategoryModel::find()->where(['parent_id' => $val['cate_id']])->exists()) {
				$scategories[$key]['switchs'] = 1;
            }
			
			// 暂时不限制级别
			$scategories[$key]['add_child'] = 1;
        }
		return Message::result(array_values($scategories));
    }
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order']);
		if(in_array($post->column, ['cate_name', 'if_show', 'sort_order'])) {
			if($post->column == 'if_show') {
				$allId = $post->id ? ScategoryModel::getDescendantIds($post->id) : array();
				if(!ScategoryModel::updateAll(['if_show' => $post->value], ['in', 'cate_id', $allId])) {
					return Message::warning(Language::get('edit_fail'));
				}
			} 
			else 
			{
				$model = new \backend\models\ScategoryForm(['cate_id' => $post->id]);
				$query = ScategoryModel::findOne($post->id);
				$query->{$post->column} = $post->value;
				if(!($scategory = $model->save($query, true))) {
					return Message::warning($model->errors);
				}
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
}
