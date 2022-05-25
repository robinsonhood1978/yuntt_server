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

use common\models\SgradeModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id SgradeController.php 2018.8.9 $
 * @author mosir
 */

class SgradeController extends \common\controllers\BaseAdminController
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
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_list')]);
			return $this->render('../sgrade.index.html', $this->params);
		}
		else
		{
			$query = SgradeModel::find();
			$query = $this->getConditions($post, $query)->orderBy(['sort_order' => SORT_ASC, 'grade_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['sgrade'] = ['need_confirm' => 1, 'sort_order' => 255];
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_add')]);
			return $this->render('../sgrade.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['need_confirm', 'sort_order', 'goods_limit', 'space_limit']);
			
			$model = new \backend\models\SgradeForm();
			if(!($sgrade = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['sgrade/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($sgrade = SgradeModel::find()->where(['grade_id' => $id])->one())) {
			return Message::warning(Language::get('no_such_sgrade'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['sgrade'] = ArrayHelper::toArray($sgrade);
			
			$this->params['page'] = Page::seo(['title' => Language::get('sgrade_edit')]);
			return $this->render('../sgrade.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['need_confirm', 'sort_order', 'goods_limit', 'space_limit']);
			
			$model = new \backend\models\SgradeForm(['grade_id' => $id]);
			if(!($sgrade = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['sgrade/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		// 默认等级不能删除
		$post->id = array_diff(explode(',', $post->id), [1]);
		foreach($post->id as $id) {
			if(StoreModel::find()->where(['sgrade' => $id])->exists()) {
				return Message::warning(sprintf(Language::get('donot_drop_by_store'), $id));
			} elseif(($model = SgradeModel::findOne($id)) && !$model->delete()) {
				return Message::warning($model->errors);
			}
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = SgradeModel::find()->orderBy(['sort_order' => SORT_ASC, 'grade_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'grade_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\SgradeExportForm::download($query->asArray()->all());		
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'need_confirm', 'sort_order']);
		if(in_array($post->column, ['need_confirm', 'sort_order'])) {
			
			$model = new \backend\models\SgradeForm(['grade_id' => $post->id]);
			$query = SgradeModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if(!$model->save($query, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['grade_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->grade_name) {
			$query->andWhere(['like', 'grade_name', $post->grade_name]);
		}
		return $query;
	}
}
