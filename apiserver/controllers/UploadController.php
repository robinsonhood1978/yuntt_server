<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;

use common\models\GoodsImageModel;
use common\models\UploadedFileModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id UploadController.php 2019.10.13 $
 * @author yxyc
 */

class UploadController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 上传图片/文件接口
	 * 只传文件，不保存到数据库
	 * @api 接口访问地址: http://api.xxx.com/upload/file
	 */
	public function actionFile()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'belong']);

		$model = new UploadedFileModel();
		$filePath = $model->upload($post->fileVal, $post->store_id, $post->belong, Yii::$app->user->id, $post->filename);
		if(!$filePath) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors ? $model->errors : Language::get('file_save_error'));
		}

		$this->params['fileUrl'] = Formatter::path($filePath);

		// 生成缩略图
		if($post->thumbnail == true) {
			$thumbnail = $model->thumbnail($filePath, 400, 400);
			$this->params['thumbnail'] = Formatter::path($thumbnail);
		}
	
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 上传图片/文件接口
	 * 上传图片并保存到数据库（目前针对卖家）
	 * 会计算图片空间使用情况 
	 * @api 接口访问地址: http://api.xxx.com/upload/add
	 */
	public function actionAdd()
	{
    	// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['belong', 'item_id']);
			
		// 获取已使用的空间大小
		$settings = StoreModel::find()->alias('s')->select('sg.space_limit')->joinWith('sgrade sg', false)->where(['store_id' => Yii::$app->user->id])->asArray()->one();
		$remain = ($settings && ($settings['space_limit'] > 0)) ? $settings['space_limit'] * 1024 * 1024 - UploadedFileModel::getFileSize(Yii::$app->user->id) : false;
		
		// 判断能否上传
        if ($remain !== false && ($remain < UploadedFile::getInstanceByName($post->fileVal)->size)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('space_limit_arrived'));
        }

		// 上传文件
		$model = new UploadedFileModel();
		$filePath = $model->upload($post->fileVal, Yii::$app->user->id, $post->belong, Yii::$app->user->id, $post->filename);
		if(!$filePath) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors ? $model->errors : Language::get('file_save_error'));
		}
			
		// 文件入库
		$fileModel = new UploadedFileModel();
		$fileModel->store_id = Yii::$app->user->id;
		$fileModel->file_type = $model->file->extension;
		$fileModel->file_name = $model->file->name;
		$fileModel->file_path = $filePath;
		$fileModel->belong = $post->belong;
		$fileModel->file_size = $model->file->size;
		$fileModel->item_id = intval($post->item_id);
		$fileModel->add_time = Timezone::gmtime();
		if($fileModel->save() == false) {
			return $respond->output(Respond::CURD_FAIL, $fileModel->errors);
		}
			
		// 生成缩略图
		if($post->thumbnail == true) {
			$thumbnail = $model->thumbnail($filePath, 400, 400);
		}
			
		// 如果是上传商品相册图片
		if(in_array($post->fileVal, ['goods_images']))
		{
			// 更新商品相册
			$imageModel = new GoodsImageModel();
			$imageModel->goods_id = intval($post->item_id);
			$imageModel->image_url = $filePath;
			$imageModel->thumbnail = $thumbnail ? $thumbnail : '';
			$imageModel->sort_order = 255;
			$imageModel->file_id = $fileModel->file_id;
			if($imageModel->save() == false) {
				return $respond->output(Respond::CURD_FAIL, $imageModel->errors);
			}
		}
		
		// 返回客户端
        $this->params = array(
            'file_id'   => $fileModel->file_id,
            //'instance'  => $post->instance,
			'file_name' => $model->file->name,
			'file_type' => $model->file->extension,
			'file_size' => $model->file->size,
			'fileUrl' 	=> Formatter::path($filePath),
			'thumbnail' => $thumbnail ? Formatter::path($thumbnail) : ''
        );

        return $respond->output(true, null, $this->params);
	}

	/**
	 * 删除图片接口
	 * @api 接口访问地址: http://api.xxx.com/upload/delete
	 */
	public function actionDelete()
	{
    	// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		// 数据库存储的是相对地址
		$post->file = str_replace(Basewind::homeUrl().'/', '', $post->file);

		// 目前只允许删除自己上传的图片
		$uploadedfile = UploadedFileModel::find()->alias('f')->select('f.file_id, f.file_path')->joinWith('goodsImage gi', false)
			->where(['or', ['f.file_path' => $post->file], ['gi.thumbnail' => $post->file]])->andWhere(['store_id' => Yii::$app->user->id])->asArray()->one();
		if(!UploadedFileModel::deleteFileByQuery(array($uploadedfile))) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_fail'));
		}

		return $respond->output(true);
	}
}
