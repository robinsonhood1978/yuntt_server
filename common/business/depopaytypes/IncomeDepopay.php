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
 * @Id IncomeDepopay.php 2018.4.17 $
 * @author mosir
 */
 
class IncomeDepopay extends BaseDepopay
{
	/**
	 * 资金流入交易
	 */
    protected $_flow = 'income';

	/**
	 * 支付类型，值有：即时到帐：INSTANT；担保交易：SHIELD；货到付款：COD
	 */
	public $_payType   	= 'INSTANT';
	
	public function _handle_trade_info($trade_info, $checkAmount = true)
	{
		// 验证金额
		if(isset($trade_info['amount'])) {
			
			$money = $trade_info['amount'];
			
			// 如果需要扣服务费
			if(isset($trade_info['fee'])) {
				$fee = $trade_info['fee'];
				if($fee < 0 || ($money < $fee)) {
					$this->setErrors("50001");
					return false;
				}
			}
			
			if($money < 0) {
				$this->setErrors("50002");
				return false;
			}
		}
		
		return true;
	}
	
	public function _handle_order_info($extra_info)
	{
		// 验证是否有order_sn，因为要通过 order_sn 找出 tradeNo
		if(!isset($extra_info['order_sn']) || empty($extra_info['order_sn'])) {
			$this->setErrors("50003");
			return false;
		}
		return true;
	}
}