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
use yii\helpers\ArrayHelper;

use common\models\AppbuylogModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;
use common\library\Business;

/**
 * @Id AppmarketCashierForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketCashierForm extends Model
{
	public $errors = null;

	public function valid($post)
	{
		if(!$post->id || !($appbuylog = AppbuylogModel::find()->select('userid,status')->where(['bid' => $post->id])->one()))  {
			$this->errors = Language::get('no_such_app');
			return false;
		}
		if($appbuylog->userid != Yii::$app->user->id) {
			$this->errors = Language::get('can_not_pay_app');
			return false;
		}
		if($appbuylog->status != Def::ORDER_PENDING) {
			$this->errors = Language::get('can_not_pay_app_for_status');
			return false;
		}
		
		return true;
	}
	
	public function submit($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$appbuylog = AppbuylogModel::find()->where(['bid' => $post->id])->one();

		// 如果应用是免费的，则直接提示购买成功/失败
		if($appbuylog->amount == 0) 
		{
			$time = Timezone::gmtime();
			
			// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
			$depopay_type = Business::getInstance('depopay')->build('buyapp');
			
			// 修改购买应用状态为交易完成
			if(!$depopay_type->_update_order_status($post->id, array('status'=> Def::ORDER_FINISHED, 'pay_time' => $time, 'end_time' => $time))) {
				$this->errors = Language::get('buy_fail');
				return false;
			}
			
			// 更新所购买的应用的过期时间
			if(!$depopay_type->_update_order_period(['userid' => Yii::$app->user->id], ArrayHelper::toArray($appbuylog))) {
				$this->errors = Language::get('buy_fail');
				return false;
			}	
		}
		return $appbuylog;
	}
}
