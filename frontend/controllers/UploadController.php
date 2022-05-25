<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\web\UploadedFile;

use common\models\StoreModel;
use common\models\UploadedFileModel;
use common\models\GoodsImageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id UploadController.php 2018.5.10 $
 * @author mosir
 */

class UploadController extends \common\controllers\BaseUserController
{
	/**
	 * 在执行Action前，判断是否有权限访问
	 * @param $action
	 */
	public function beforeAction($action)
    {
		if(in_array($action->id, ['add']) && !$this->visitor['store_id']) {
			return $this->accessWarning();
		}
		return parent::beforeAction($action);
	}

	/* 仅上传图片，不保存到数据库 */
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->post(), true);
		$store_id = intval($this->visitor['store_id']);

		$model = new UploadedFileModel();
		$filePath = $model->upload($post->fileVal, $store_id, $post->belong, Yii::$app->user->id, $post->filename);
		if(!$filePath) {
			return Message::warning($model->errors ? $model->errors : Language::get('file_save_error'));
		}
		return Message::result($filePath);
	}
	
	/* 上传图片并保存到数据库（目前针对卖家） */
	public function actionAdd()
	{
    	if (Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			// 获取已使用的空间大小
			$settings = StoreModel::find()->alias('s')->select('sg.space_limit')->joinWith('sgrade sg', false)->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			$remain = ($settings && ($settings['space_limit'] > 0)) ? $settings['space_limit'] * 1024 * 1024 - UploadedFileModel::getFileSize($this->visitor['store_id']) : false;
			
			// 判断能否上传
            if ($remain !== false && ($remain < UploadedFile::getInstanceByName($post->fileVal)->size)) {
				return Message::warning(Language::get('space_limit_arrived'));
            }
			
			$model = new UploadedFileModel();
			$filePath = $model->upload($post->fileVal, $this->visitor['store_id'], $post->belong, Yii::$app->user->id, $post->filename);
			if(!$filePath) {
				return Message::warning($model->errors ? $model->errors : Language::get('file_save_error'));
			}
			
			// 文件入库
			$fileModel = new UploadedFileModel();
			$fileModel->store_id = $this->visitor['store_id'];
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
			
			// 返回到客户端的信息
			$ret_info = array(); 
			$instance = Yii::$app->request->get('instance', '');
			
			// 如果是上传商品相册图片
			if(in_array($instance, ['goods_image']))
			{
				// 生成缩略图
				$thumbnail = $model->thumbnail($filePath, 400, 400);

				// 更新商品相册
				$imageModel = new GoodsImageModel();
				$imageModel->goods_id = $post->item_id;
				$imageModel->image_url = $filePath;
				$imageModel->thumbnail = $thumbnail;
				$imageModel->sort_order = 255;
				$imageModel->file_id = $fileModel->file_id;
				if($imageModel->save() == false) {
					return Message::warning($imageModel->errors);	
				}
				$ret_info = array_merge($ret_info, array('thumbnail' => $thumbnail));
			}
		
			// 返回客户端
        	$ret_info = array_merge($ret_info, array(
            	'file_id'   => $fileModel->file_id,
            	'file_path' => $filePath,
            	'instance'  => $instance,
				'file_name' => $model->file->name,
				'file_type' => $model->file->extension
        	));
        	return Message::result($ret_info);
    	}
	}
}