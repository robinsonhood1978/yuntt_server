<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\AppmarketModel;
use common\models\ApprenewalModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;
use common\library\Def;

/**
 * @Id AppmarketDeleteForm.php 2018.8.24 $
 * @author mosir
 */
class AppmarketDeleteForm extends Model
{
	public $aid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		foreach(explode(',', $this->aid) as $id) {
			if($model = AppmarketModel::find()->select('aid,appid,logo')->where(['aid' => $id])->one()) {
				if($query = ApprenewalModel::find()->select('expired')->where(['appid' => $model->appid])->orderBy(['expired' => SORT_DESC])->one()) {
					if($query->expired > Timezone::gmtime()) {
						
						// 未过期，不允许删除
						$this->errors = sprintf(Language::get('drop_fail'), Language::get($model->appid));
						return false;
					}
				}
				// 删掉应用表
				if($model->delete() === false) {
					$this->errors = $model->errors;
					return false;
				}
				
				$promotool = Promotool::getInstance($model->appid)->build();
				
				// 删掉应用设置表
				$promotool->delete();
				
				// 删除商品应用对应表
				$promotool->deleteItem();
				
				// 删除应用图
				UploadedFileModel::deleteFileByName($model->logo);
				
				// 删除应用描述图
				UploadedFileModel::deleteFileByQuery(UploadedFileModel::find()->where(['store_id' => 0, 'item_id' => $id, 'belong' => Def::BELONG_APPMARKET])->asArray()->all());
			}
		}
		return true;
	}
}
