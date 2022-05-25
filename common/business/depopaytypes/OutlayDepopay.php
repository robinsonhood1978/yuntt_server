<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\business\depopaytypes;

use yii;

use common\business\BaseDepopay;

/**
 * @Id OutlayDepopay.php 2018.4.15 $
 * @author mosir
 */
 
class OutlayDepopay extends BaseDepopay
{
	/**
	 * 资金流出交易
	 */
    protected $_flow = 'outlay';

	/**
	 * 支付类型，值有：即时到帐：INSTANT；担保交易：SHIELD；货到付款：COD
	 */
	public $_payType   	= 'INSTANT';
	
	public function _handle_trade_info($trade_info, $checkAmount = true)
	{
		// 如果是退款操作，无需验证金额是否足够
		if($checkAmount === false){
			return true;
		}
		
		// 验证是否有足够的金额用于支出
		if(isset($trade_info['amount'])) {
			
			$money = $trade_info['amount'];
			if($money < 0) {
				$this->setErrors("50002");
				return false;
			}
			
			// 如果需要扣服务费，则加上服务费后再验证
			if(isset($trade_info['fee'])) {
				if($trade_info['fee'] < 0) {
					$this->setErrors("50001");
					return false;
				}
				$money += $trade_info['fee'];
			}
			
			if(!parent::_check_enough_money($money, $trade_info['userid'])) {
				$this->setErrors("50019");
				return false;
			}
		}
		return true;
	}
}