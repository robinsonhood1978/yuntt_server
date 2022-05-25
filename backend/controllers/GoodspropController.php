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

use common\models\GoodsPropModel;
use common\models\GoodsPropValueModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id GoodspropController.php 2018.8.15 $
 * @author mosir
 */

class GoodspropController extends \common\controllers\BaseAdminController
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
		$query = GoodsPropModel::find()->indexBy('pid')->orderBy(['sort_order' => SORT_ASC, 'pid' => SORT_DESC]);
		$page = Page::getPage($query->count(), 20);
		$props = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($props as $key => $val) {
			$props[$key]['switchs'] = GoodsPropValueModel::find()->where(['pid' => $val['pid']])->exists();
		}
		$this->params['goodsprop'] = $props;
		$this->params['pagination'] = Page::formatPage($page);
		
		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'treetable/ptree.js,inline_edit.js']);
		
		$this->params['page'] = Page::seo(['title' => Language::get('goods_prop')]);
		return $this->render('../goodsprop.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['prop'] = ['pid' => 0, 'sort_order' => 255, 'status' => 1];

			$this->params['page'] = Page::seo(['title' => Language::get('prop_add')]);
			return $this->render('../goodsprop.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['is_color', 'status', 'sort_order']);
			
			$model = new \backend\models\GoodsPropForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['goodsprop/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($prop = GoodsPropModel::findOne($id))) {
			return Message::warning(Language::get('no_such_prop'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['prop'] = ArrayHelper::toArray($prop);
			
			$this->params['page'] = Page::seo(['title' => Language::get('prop_edit')]);
			return $this->render('../goodsprop.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['is_color', 'status', 'sort_order']);
			
			$model = new \backend\models\GoodsPropForm(['pid' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['goodsprop/index']);		
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \backend\models\GoodsPropDeleteForm(['pid' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['goodsprop/index']);	
	}
	
	public function actionAddvalue()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($prop = GoodsPropModel::findOne($id))) {
			return Message::warning(Language::get('no_such_prop'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['propvalue'] =  ['sort_order' => 255, 'status' => 1];
			$this->params['prop'] = ArrayHelper::toArray($prop);
			
			$this->params['page'] = Page::seo(['title' => Language::get('add_pvalue')]);
			return $this->render('../goodsprop.value.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status', 'sort_order']);
			
			$model = new \backend\models\GoodsPropValueForm(['pid' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['goodsprop/index']);		
		}
	}
	
	public function actionEditvalue()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($propvalue = GoodsPropValueModel::findOne($id))) {
			return Message::warning(Language::get('no_such_pvalue'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['propvalue'] =  ArrayHelper::toArray($propvalue);
			$this->params['prop'] = GoodsPropModel::find()->select('name,is_color')->where(['pid' => $propvalue->pid])->asArray()->one();
			
			$this->params['page'] = Page::seo(['title' => Language::get('edit_pvalue')]);
			return $this->render('../goodsprop.value.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status', 'sort_order']);
			
			$model = new \backend\models\GoodsPropValueForm(['pid' => $propvalue->pid, 'vid' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['goodsprop/index']);		
		}
	}
	
	public function actionDeletevalue()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = GoodsPropValueModel::findOne($post->id);
		if($model && !$model->delete()) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['goodsprop/index']);	
	}
	
	/* 给分类分配属性 */
	public function actionDistribute()
	{
		$id = intval(Yii::$app->request->get('cate_id'));
		if(!$id || !($gcategory = GcategoryModel::find()->where(['cate_id' => $id, 'store_id' => 0])->one())) {
			return Message::warning(Language::get('no_such_gcategory'));
		}
			
		if(!Yii::$app->request->isPost)
		{
			$this->params['ancestor'] = GcategoryModel::getAncestor($id);
			
			$query = GoodsPropModel::find()->select('pid,name')->with('goodsPropValue')->indexBy('pid')->orderBy(['sort_order' => SORT_ASC, 'pid' => SORT_DESC]);
			$page = Page::getPage($query->count(), 20);
			$props = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			$model = new \backend\models\GoodsPropDistributeForm(['cate_id' => $id]);	
			$this->params['props'] = $model->formatData($props);
			$this->params['pagination'] = Page::formatPage($page);
			
			$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
			
			$this->params['page'] = Page::seo(['title' => Language::get('distribute_prop')]);
			return $this->render('../goodsprop.distribute.html', $this->params);
			
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\GoodsPropDistributeForm(['cate_id' => $id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('save_ok'), [
				'goodsprop/distribute', 'cate_id' => $id, 'page' => Yii::$app->request->get('page')]);
		}
	}
	
	/* 异步取所有下级 */
   	public function actionChild()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(!$post->id) {
			return Message::warning(false);
		}
		$list = GoodsPropValueModel::find()->alias('pv')->select('pv.*,gp.is_color')->joinWith('goodsProp gp', false)->where(['pv.pid' => $post->id])->asArray()->all();
		return Message::result(array_values($list));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'value']);
		if(in_array($post->column, ['prop_status', 'pv_status', 'prop_sort_order', 'pv_sort_order'])) {
			if($post->column == 'prop_status') {
				if(!GoodsPropModel::updateAll(['status' => $post->value], ['pid' => $post->id])) {
					return Message::warning(Language::get('edit_fail'));
				}
			}
			elseif($post->column == 'prop_sort_order') {
				if(!GoodsPropModel::updateAll(['sort_order' => $post->value], ['pid' => $post->id])) {
					return Message::warning(Language::get('edit_fail'));
				}
			} 
			elseif($post->column == 'pv_status')
			{
				if(!GoodsPropValueModel::updateAll(['status' => $post->value], ['vid' => $post->id])) {
					return Message::warning(Language::get('edit_fail'));
				}
			}
			elseif($post->column == 'pv_sort_order')
			{
				if(!GoodsPropValueModel::updateAll(['sort_order' => $post->value], ['vid' => $post->id])) {
					return Message::warning(Language::get('edit_fail'));
				}
			}
			return Message::display(Language::get('edit_ok'));	
		}
    }
}
