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
use yii\helpers\Json;

use common\models\UserModel;
use common\models\CashcardModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id CashcardController.php 2018.8.20 $
 * @author mosir
 */

class CashcardController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page', 'printed', 'actived']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'inline_edit.js,jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_list')]);
			return $this->render('../cashcard.index.html', $this->params);
		}
		else
		{
			$query = CashcardModel::find()->select('id,name,cardNo,password,money,add_time,expire_time,active_time,useId,printed');
			$query = $this->getConditions($post, $query)->orderBy(['id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['expire_time'] = Timezone::localDate('Y-m-d H:i:s', $value['expire_time']);
				$list[$key]['active_time'] = Timezone::localDate('Y-m-d H:i:s', $value['active_time']);
				$list[$key]['username'] = UserModel::find()->select('username')->where(['userid' => $value['useId']])->scalar();
			}
			
			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_add')]);
			return $this->render('../cashcard.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['quantity']);
			
			$model = new \backend\models\CashcardForm();
			if(!($cashcard = $model->create($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['cashcard/index']);		
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if(!$id || !($cashcard = CashcardModel::find()->where(['id' => $id])->one())) {
			return Message::warning(Language::get('no_such_cashcard'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['cashcard'] = ArrayHelper::toArray($cashcard);
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			$this->params['page'] = Page::seo(['title' => Language::get('cashcard_edit')]);
			return $this->render('../cashcard.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \backend\models\CashcardForm(['id' => $id]);
			if(!($cashcard = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['cashcard/index']);		
		}
	}
	
	/**
	 * 目前只有用户不存在了，或充值卡未分配给用户，才允许删除
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			if($id && ($model = CashcardModel::findOne($id)) && (!$model->useId || !UserModel::findOne($model->useId))) {
				if(!$model->delete()) {
					return Message::warning($model->errors);
				}
			}
		}
		return Message::display(Language::get('drop_ok'));
	}

	/**
	 * 设置制卡状态
	 */
	public function actionPrinted()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['value']);
		CashcardModel::updateAll(['printed' => $post->value], ['in', 'id', explode(',', $post->id)]);
		return Message::display(Language::get('set_ok'));
	}
	
	/* 异步修改数据 */
    public function actionEditcol()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(in_array($post->column, ['printed', 'password', 'name'])) {
			$model = new \backend\models\CashcardForm(['id' => $post->id]);
			$query = CashcardModel::findOne($post->id);
			if(!$query) {
				return Message::warning(Language::get('no_data'));
			} elseif($query->active_time > 0) {
				return Message::warning(Language::get('actived_disallow'));
			}
			$query->{$post->column} = $post->value;
			if(!($cashcard = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
    }
	
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = CashcardModel::find()->select('id,name,cardNo,password,money,add_time,expire_time,active_time,useId,printed')->orderBy(['id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\CashcardExportForm::download($query->asArray()->all());		
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['cardNo', 'name', 'add_time_from', 'add_time_to', 'actived', 'printed'])) {
					return true;
				}
			}
			return false;
		}
		if($post->cardNo) {
			$query->andWhere(['cardNo' => $post->cardNo]);
		}
		if($post->name) {
			$query->andWhere(['like', 'name', $post->name]);
		}
		
		if($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'add_time', $post->add_time_from], ['<=', 'add_time', $post->add_time_to]]);
		}
		if($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'add_time', $post->add_time_from]);
		}
		if(!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'add_time', $post->add_time_to]);
		}
		if($post->actived == 1) {
			$query->andWhere(['active_time' => 0]);
		}
		if($post->actived == 2) {
			$query->andWhere(['>', 'active_time', 0]);
		}
		if($post->printed == 1) {
			$query->andWhere(['printed' => 0]);
		}
		if($post->printed == 2) {
			$query->andWhere(['>', 'printed', 0]);
		}
		
		return $query;
	}
}
