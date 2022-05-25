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

use common\models\IntegralModel;

use common\library\Language;

/**
 * @Id IntegralRechargeForm.php 2018.8.6 $
 * @author mosir
 */
class IntegralRechargeForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
    public function valid($post)
	{
		if(!in_array($post->flow, ['add', 'minus'])) {
			$this->errors = Language::get('recharge_flow_fail');
			return false;
		}
		return true;
	}
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$params = array(
			'userid' 	=> $this->userid,
			'flow'		=> $post->flow,
			'type' 	  	=> 'admin_handle',
			'amount'    => floatval($post->amount),
			'flag'      => $post->flag
		);
		$result = IntegralModel::updateIntegral($params);
		return ($result !== false) ? $result : false;

	}
}
