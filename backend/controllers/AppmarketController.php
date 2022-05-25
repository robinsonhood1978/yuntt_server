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
use yii\helpers\Url;

use common\models\AppmarketModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id AppmarketController.php 2018.8.24 $
 * @author mosir
 */

class AppmarketController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['applist'] = AppmarketModel::getList();
			
			// 属于应用的附件（游离图）
			$appmarket['desc_images'] = UploadedFileModel::find()->select('file_id,file_type,file_path,file_name')->where(['store_id' => 0, 'item_id' => 0, 'belong' => Def::BELONG_APPMARKET])->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			$this->params['appmarket'] = array_merge($appmarket, ['status' => 1]);
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_APPMARKET,
                'item_id' 		=> 0,
                'upload_url' 	=> Url::toRoute(['upload/add']),
                'multiple' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['page'] = Page::seo(['title' => Language::get('appmarket_add')]);
			return $this->render('../appmarket.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status', 'category']);
			
			$model = new \backend\models\AppmarketForm();
			if(!($appmarket = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('add_ok'), ['appmarket/index']);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id', 0));
		if(!$id || !($appmarket = AppmarketModel::find()->where(['aid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_app'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['applist'] = AppmarketModel::getList();
			
			// 属于应用的附件
			$appmarket['desc_images'] = UploadedFileModel::find()->select('file_id,file_type,file_path,file_name')->where(['store_id' => 0, 'item_id' => $id, 'belong' => Def::BELONG_APPMARKET])->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			$this->params['appmarket'] = $appmarket;
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_APPMARKET,
                'item_id' 		=> $id,
                'upload_url' 	=> Url::toRoute(['upload/add']),
                'multiple' 		=> true
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['page'] = Page::seo(['title' => Language::get('appmarket_edit')]);
			return $this->render('../appmarket.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status', 'category']);
			
			$model = new \backend\models\AppmarketForm(['aid' => $id]);
			if(!($appmarket = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['appmarket/index']);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_such_app'));
		}
		$model = new \backend\models\AppmarketDeleteForm(['aid' => $post->id]);
		if(!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['appmarket/index']);
	}
	
	/* 异步删除编辑器上传的图片 */
	public function actionDeleteimage()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach(explode(',', $post->id) as $id) {
			UploadedFileModel::deleteFileByQuery(UploadedFileModel::find()->where(['belong' => Def::BELONG_APPMARKET, 'file_id' => $id])->asArray()->all());
		}
		return Message::display(Language::get('drop_ok'));
	}
}
