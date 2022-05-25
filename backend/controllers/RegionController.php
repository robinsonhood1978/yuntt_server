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

use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id RegionController.php 2018.8.13 $
 * @author mosir
 */

class RegionController extends \common\controllers\BaseAdminController
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
		$regions = RegionModel::getList(0, false, false);
		foreach ($regions as $key => $val)
        {
            $regions[$key]['switchs'] = 0;
			if(RegionModel::find()->where(['parent_id' => $val['region_id']])->exists()) {
				$regions[$key]['switchs'] = 1;
            }
        }
		$this->params['regions'] = $regions;
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css,dialog/dialog.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.ui/jquery.ui.js,dialog/dialog.js,treetable/rtree.js,inline_edit.js']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('region_setting')]);
		return $this->render('../region.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['region'] = ['parent_id' => intval(Yii::$app->request->get('pid')), 'sort_order' => 255];
			$this->params['parents'] = RegionModel::getOptions(-1, null, 0, false);
			
			$this->params['page'] = Page::seo(['title' => Language::get('region_add')]);
			return $this->render('../region.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\RegionForm();
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}

			return Message::popSuccess();
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($region = RegionModel::findOne($id))) {
			return Message::warning(Language::get('no_such_region'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['region'] = ArrayHelper::toArray($region);
			$this->params['parents'] = RegionModel::getOptions(-1, $id, 0, false);
			
			$this->params['page'] = Page::seo(['title' => Language::get('region_edit')]);
			return $this->render('../region.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['sort_order', 'parent_id']);
			
			$model = new \backend\models\RegionForm(['region_id' => $id]);
			if(!($region = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess();	
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$post->id = explode(',', $post->id);
		foreach($post->id as $id) {
			if($id && ($allId = RegionModel::getDescendantIds($id))) {
				RegionModel::deleteAll(['in', 'region_id', $allId]);
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
		
		$regions = RegionModel::getList($post->id, 0, false);
		foreach ($regions as $key => $val)
        {
            $regions[$key]['switchs'] = 0;
			if(RegionModel::find()->where(['parent_id' => $val['region_id']])->exists()) {
				$regions[$key]['switchs'] = 1;
            }
			
			// 暂时不限制级别
			$regions[$key]['add_child'] = 1;
        }
		return Message::result(array_values($regions));
    }
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'sort_order']);
		
		if(in_array($post->column, ['region_name', 'if_show', 'sort_order'])) {
			if($post->column == 'if_show') {
				$allId = $post->id ? RegionModel::getDescendantIds($post->id) : array();
				$result = RegionModel::updateAll(['if_show' => $post->value], ['in', 'region_id', $allId]);
			} 
			else 
			{
				$model = new \backend\models\RegionForm(['region_id' => $post->id]);
				$query = RegionModel::findOne($post->id);
				$query->{$post->column} = $post->value;
				if(!($region = $model->save($query, true))) {
					return Message::warning($model->errors);
				}
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
}
