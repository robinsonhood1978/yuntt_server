<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 
use yii\web\UploadedFile;

use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id UploadForm.php 2018.5.11 $
 * @author mosir
 */
class UploadForm extends Model
{
	public $file;
	public $allowed_type;
	public $allowed_size;
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
   			[['file'], 'file', 'skipOnEmpty' => false, 'extensions' => $this->allowed_type, 'maxSize' => $this->allowed_size, 'checkExtensionByMimeType' => false]
  		];
    }
	
	/**
	 * 上传文件
	 * 如果安装了OSS上传插件，则同时上传到OSS
	 * 注意：即使使用OSS上传，也需要将文件先上传到服务器，因为OSS上传也是将实际文件上传到OSS服务器
	 */
	public function upload($path = '', $baseName = false)
	{
		if($baseName === false) {
			$baseName = $this->file->baseName;
		}

		// 上传路径
		$file = $path . '/' .  $baseName . '.' . $this->file->extension;
		$object = str_replace(Def::fileSavePath() . '/', '', $file);
		
		// 先上传到本地
		if(!$this->file->saveAs($file)) {
			return false;
		}

		// 上传到OSS云存储
		if(($oss = Plugin::getInstance('oss')->autoBuild())) 
		{
			// 如果上传成功，删除本地文件，释放空间
			if(($saveUrl = $oss->upload($object, $file))) {
				unlink($file);
			}

			// 如果上传不成功希望返回错误，则启用下面两行
			else {
				//$this->errors = 'oss config fail';
				//return false;
			}
		}

		return $saveUrl ? $saveUrl : $object;
	}
	public function filename()
	{
		return Timezone::localDate('YmdHis', Timezone::gmtime()) . mt_rand(100,999);
	}
	public function getInstance($file, $multiple = false)
	{
		if($multiple == true) {
			return UploadedFile::getInstancesByName($file);
		}
		return UploadedFile::getInstanceByName($file);
	}
}