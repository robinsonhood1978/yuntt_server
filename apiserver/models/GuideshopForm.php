<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 
use yii\helpers\ArrayHelper;

use common\models\GuideshopModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id GuideshopForm.php 2020.2.23 $
 * @author yxyc
 */
class GuideshopForm extends Model
{
	public $errors = null;
	
	/** 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		$fields = ['owner', 'name', 'region_id', 'address', 'phone_mob', 'banner'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}
		
		// 例外判断
		if(isset($post->phone_mob) && (Basewind::isPhone($post->phone_mob) == false)) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}
		
		return true;
	}
	
	/**
	 * 新增/编辑
	 * 如果是编辑模式下，当门店没有审核通过的情况下，每一次修改资料都会改变门店状态为重新审核
	 */
	public function save($post, $valid = true)
	{
		if($valid === true && !($this->valid($post))) {
			return false;
		}
		
		if(!($model = GuideshopModel::find()->where(['userid' => Yii::$app->user->id])->one())) {
			$model = new GuideshopModel();
			$model->userid = Yii::$app->user->id;
			$model->created = Timezone::gmtime();
			$model->inviter = 0; // 推广员ID
		}
		else
		{
			// 如果门店被平台关闭，则不允许编辑
			if($model->status == Def::STORE_CLOSED) {
				$this->errors = Language::get('shop_closed');
				return false;
			}

			// 如果不是审核通过 和 审核拒绝模式，不允许编辑
			if(!in_array($model->status, [Def::STORE_OPEN, Def::STORE_NOPASS])) {
				$this->errors = Language::get('shop_verfiying');
				return false;
			}

			// 此处可以加入审核通过后，不允许修改某些字段的控制
			// TODO...
		}

		if($model->status != Def::STORE_OPEN) {
			$model->status = Def::STORE_APPLYING; // 审核状态
			$model->remark = Language::get('remark'); // 进入审核模式
		}
		
		if(isset($post->owner)) $model->owner = $post->owner;
		if(isset($post->phone_mob)) $model->phone_mob = $post->phone_mob;
		if(isset($post->name)) $model->name = $post->name;
		if(isset($post->longitude)) $model->longitude = $post->longitude;
		if(isset($post->latitude)) $model->latitude = $post->latitude;
		if(isset($post->address)) $model->address = $post->address;
		if(isset($post->region_id)) {
			$model->region_id = $post->region_id;
			$model->region_name = implode(' ', RegionModel::getArrayRegion($post->region_id));
		}
		if(isset($post->banner)) {
			if(Basewind::getCurrentApp() == 'api') {
				$model->banner = $this->getFileSavePath($post->banner);
			}
		}

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		return ArrayHelper::toArray($model);
	}
	
	/**
	 * 验证当前用户是否申请了团长门店
	 * 如果申请了，不允许再申请第二个门店，一个团长只允许申请一个门店
	 */
	public function exists($post)
	{
		if(!GuideshopModel::find()->where(['userid' => Yii::$app->user->id])->exists()) {
			$this->errors = Language::get('on_such_item');
			return false;
		}
		return true;
	}
	
	/**
	 * 如果是新增，则一律判断
	 * 如果是编辑，则设置值了才判断
	 */
	private function isempty($post, $fields)
	{
		if($this->exists($post)) {
			if(isset($post->$fields)) {
				return empty($post->$fields);
			}
			return false;
		}
		return empty($post->$fields);
	}

	/**
	 * 如果是本地存储，存相对地址，如果是云存储，存完整地址
	 * 此处主要考虑站点域名修改后导致的路径错误问题，所以存储相对路径更可靠
	 */
	private function getFileSavePath($image = '')
	{
		if(stripos($image, Def::fileSaveUrl()) !== false) {
			return str_replace(Def::fileSaveUrl() . '/', '', $image);
		} 
		return $image;
	}
}
