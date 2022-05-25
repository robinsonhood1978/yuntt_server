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

use common\models\TestModel;

use common\library\Basewind;
use common\library\Language;
use yii\helpers\ArrayHelper;

/**
 * @Id AddressForm.php 2018.10.23 $
 * @author yxyc
 */
class TestForm extends Model
{
	public $addr_id = 0;
	public $errors = null;
	
	/** 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		$fields = ['trade_no', 'content'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
        if($valid === true && !($this->valid($post))) {
			return false;
		}
        
        $model = new TestModel();
		
		if(isset($post['content'])) $model->content = $post['content'];
		if(isset($post['trade_no'])) $model->trade_no = $post['trade_no'];
		if(isset($post['latipay_order_id'])) $model->latipay_order_id = $post['latipay_order_id'];

        // var_dump($model->content);
        
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
        
		return ArrayHelper::toArray($model);
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
}
