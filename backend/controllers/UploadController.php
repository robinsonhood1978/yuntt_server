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

use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Timezone;

/**
 * @Id UploadController.php 2018.8.22 $
 * @author mosir
 */

class UploadController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	/* 仅上传图片，不保存到数据库 */
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->post(), true);
		$store_id = Yii::$app->request->get('store_id', 0);

		$model = new UploadedFileModel();
		$filePath = $model->upload($post->fileVal, $store_id, $post->belong, Yii::$app->user->id, $post->filename);
		if(!$filePath) {
			return Message::warning($model->errors ? $model->errors : Language::get('file_save_error'));
		}
		return Message::result($filePath);
	}
	
	/* 上传图片并保存到数据库 */
	public function actionAdd()
	{
    	if (Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$store_id = Yii::$app->request->get('store_id', 0);

			$model = new UploadedFileModel();
			$filePath = $model->upload($post->fileVal, $store_id, $post->belong, Yii::$app->user->id, $post->filename, true, $post->archived);
			if(!$filePath) {
				return Message::warning($model->errors ? $model->errors : Language::get('file_save_error'));
			}
			
			// 文件入库
			$fileModel = new UploadedFileModel();
			$fileModel->store_id = Yii::$app->request->get('store_id', 0);
			$fileModel->file_type = $model->file->extension;
			$fileModel->file_name = $model->file->name;
			$fileModel->file_path = $filePath;
			$fileModel->belong = $post->belong;
			$fileModel->file_size = $model->file->size;
			$fileModel->item_id = $post->item_id;
			$fileModel->add_time = Timezone::gmtime();
			if($fileModel->save() == false) {
				return Message::warning($fileModel->errors);	
			}

			// 返回客户端
        	$ret_info = array(
            	'file_id'   => $fileModel->file_id,
            	'file_path' => $filePath,
            	'instance'  => Yii::$app->request->get('instance', ''),
				'file_name' => $model->file->name,
				'file_type' => $model->file->extension
        	);
        	return Message::result($ret_info);
    	}
	}
}