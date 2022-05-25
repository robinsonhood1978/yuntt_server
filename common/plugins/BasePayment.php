<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins;

use yii;
use yii\helpers\Url;

use common\models\OrderModel;
use common\models\CodModel;
use common\models\PluginModel;
use common\models\DepositTradeModel;
use common\models\DepositAccountModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Business;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id BasePayment.php 2018.6.1 $
 * @author mosir
 */
 
class BasePayment extends BasePlugin
{
	/**
	 * 支付插件系列
	 * @var string $instance
	 */
	protected $instance = 'payment';
	
	/* 获取支付表单 */
    public function getPayform()
    {
        return $this->createPayform();
    }

    /**
	 * 获取规范的支付表单数据
	 * @param array $params
	 * @param string $gateway
	 * @param string $method post|get
	 */
    public function createPayform($params = array(), $gateway = '', $method = 'post')
    {
		$payment = Plugin::getInstance('payment')->build($this->code)->getInfo();
        return array(
			'payment_code' 	=> $this->code,
            'online'    	=> $payment['is_online'],
            //'desc'      	=> $payment['desc'],
            'method'    	=> $method,
            'gateway'   	=> $gateway,
            'params'    	=> $params,
        );
    }
	
	/**
	 * 获取通知地址 
	 * @param string $payTradeNo 支付交易号
	 */
    public function createNotifyUrl($payTradeNo = '')
    {
        return Url::toRoute(['paynotify/notify', 'payTradeNo' => $payTradeNo], true);
    }

    /**
	 * 获取返回地址 
	 * @param string $payTradeNo 支付交易号
	 */
    public function createReturnUrl($payTradeNo = '')
    {
		// for API
		if($this->params->callback) {
			return $this->params->callback . '?payTradeNo='.$payTradeNo;
		}

		return Url::toRoute(['paynotify/index', 'payTradeNo' => $payTradeNo], true);
	}
	
	/**
	 * 获取外部交易号 
	 * @param array $orderInfo 订单信息
	 * @param string 要获取的支付交易号的长度
	 */
    public function getPayTradeNo($orderInfo, $length = 0)
    {
		$payTradeNo = DepositTradeModel::genPayTradeNo($orderInfo, $length);
		DepositTradeModel::updateAll(['payTradeNo' => $payTradeNo], ['in', 'trade_id', array_keys($orderInfo['tradeList'])]);
					
        return $payTradeNo;
    }
	
	/**
	 * 获取支付回调返回的数据
	 */
	public function getNotify()
	{
		if($post = Yii::$app->request->post()) {
			return $post;
		}

		// 存储微信的回调
		$notify = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
		libxml_disable_entity_loader(true);	 //禁止引用外部xml实体
		$array = json_decode(json_encode(simplexml_load_string($notify, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($array) {
			return $array;
		}

		return $notify ? $notify : Yii::$app->request->get();
	}
	
	/**
	 * 返回给支付网关的回馈信息
	 * @param bool $result
	 */
	public function verifyResult($result = false)
	{
		if ($result){
            echo 'success';
        } 
		else {
            echo 'fail';
        }
	}

	/**
	 * 获取已启用并满足一定条件的支付方式
	 * @param int $store_id 店铺ID 这个主要是为了获取店铺配置的货到付款方式
	 * @param bool $removeImproper 是否移除不合理的支付方式
	 * @param array $extraParams 其他搜索条件
	 */
    public function getEnabled($store_id = 0, $removeImproper = true, $extraParams = array())
    {
		$query = PluginModel::find()->where(['instance' => $this->instance, 'enabled' => 1])->indexBy('code');
		if($extraParams) {
			$query->andWhere($extraParams);
		}
		$payments = $query->asArray()->all();

		// 如果卖家没有配置货到付款，则去除该支付方式
		if(isset($payments['cod']) && (!($cod = CodModel::checkAndGetInfo($store_id)) || empty($cod->regions))) {
			unset($payments['cod']);
		}

		// 只取合适的支付方式
		if($removeImproper === true)
		{
			$suitable = array();// 合适的
			
			// 如果是H5端
			if(Basewind::getCurrentApp() == 'wap') {
				
				// 微信公众号内访问
				if(Basewind::isWeixin()) {
					$suitable = ['deposit', 'cod', 'tenpay_wap', 'unionpay', 'wxpay'];
				}
				// 支付宝内嵌浏览器
				elseif(Basewind::isAlipay()) {
					$suitable = ['deposit', 'cod', 'alipay_wap', 'unionpay'];
				}
				else {
					$suitable = ['deposit', 'cod', 'alipay_wap', 'tenpay_wap', 'unionpay', 'wxh5pay']; 
				}
			}

			// 如果是接口调用
			elseif(Basewind::getCurrentApp() == 'api') {

				// 如果是微信端(即：微信小程序, 微信公众号)
				if(Basewind::isWeixin()) {
					$suitable = ['deposit', 'wxmppay', 'wxpay', 'latipay'];
				}
				// 如果是头条端（即：头条小程序）
				elseif(Basewind::isToutiao()) {
					$suitable = ['deposit', 'alipay_app'];
				}
				// 如果是移动设备
				elseif(Basewind::isMobileDevice()) 
				{
					// 如果是在支付宝内嵌浏览器或支付宝小程序
					if(Basewind::isAlipay()) {
						$suitable = ['deposit', 'alipay_wap'];
					}
					// 其他浏览器或APP
					else {
						$suitable = ['deposit', 'alipay_wap', 'alipay_app', 'wxh5pay', 'wxapppay'];
					}
				}
				// 其他（可能是PC）
			}
			else {
				
				// 如果是PC端
				$suitable = ['deposit', 'cod', 'alipay', 'tenpay', 'unionpay', 'wxnativepay', 'latipay'];
			}
			
			foreach($payments as $key => $payment) {
				if(!in_array($payment['code'], $suitable)) {
					unset($payments[$key]);
				}
			} 
		}
		
		if($payments)
		{
			// 排序，使得在收银台界面余额支付排在第一位
			$tmp = array();
			foreach($payments as $key => $payment)
			{
				if(in_array($payment['code'], array('deposit'))) {
					$tmp[$key] = $payment;
					unset($payments[$key]);
					break;
				}
			}
			$payments = $tmp + $payments;
		}
		
		return $payments;
	}
	
	/**
	 * 获取支付方式的键值
	 * @param array $payments
	 */
	public function getKeysOfPayments($payments = array())
	{
		$keys = array();
		foreach($payments as $key => $payment) {
			$keys[] = $payment['code'];
		}
		
		return $keys;
	}

	/**
	 * 根据订单数据，获取可用的支付方式
	 * @param array $orderInfo 订单数据
	 * @param bool $showDepositPay 是否显示余额支付方式
	 * @param bool $showCodPay 是否显示货到付款方式 
	 */
	public function getAvailablePayments($orderInfo, $showDepositPay = true, $showCodPay = false)
	{
		$selected = $errorMsg = false;
		$all_payments = $cod_payments = array();
		
		$payments = $this->getEnabled(0);
		foreach ($payments as $key => $payment)
		{
			// 如果支付的金额为零，那么仅显示余额付款和货到付款，不显示网银等支付
			if($orderInfo['amount'] <= 0) {
				if(!in_array($payment['code'], array('deposit', 'cod'))) continue;
			}
			if(in_array($payment['code'], array('deposit')))
			{
				if($showDepositPay === true)
				{
					$depositAccount = DepositAccountModel::find()->select('pay_status,money')->where(['userid' => Yii::$app->user->id])->asArray()->one();
					
					if(in_array($depositAccount['pay_status'], array('ON'))) {
						if($orderInfo['amount'] > $depositAccount['money']) {
							$payment['disabled'] = 1;
							$payment['disabled_desc'] = Language::get('balancepay_not_enough');
						} else {
							$selected = true;
							$payment['selected'] = 1;
						}
					}
					else
					{
						$payment['disabled'] = 1;
						$payment['disabled_desc'] = Language::get('balancepay_disabled');
					}
				}
				else
				{
					$payment = false;
				}
			}
            
			if($payment !== false) {
				if ($selected === false && !$payment['disabled']) {
					$selected = true;
					$payment['selected'] = 1;
				}
				
				$all_payments[$payment['code']] = $payment;
			}
		}
		
		// 检查是否支持货到付款(目前只有购物订单允许使用货到付款，其他如购买应用等不允许)
		if($showCodPay === true && in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER))) 
		{
			if($cod_payments = OrderModel::checkMergePayCodPaymentEnable($orderInfo['orderList'])){
				$cod_payment = current($cod_payments);
				
				// 如果还没有选择默认的支付方式，则选择
				if($selected === false) $cod_payment['selected'] = 1;
				$all_payments[$cod_payment['code']] = $cod_payment;
			}
		}
		
		if(empty($all_payments)) {
			$errorMsg = Language::get('store_no_payment');
		}
		
		return array($all_payments, $cod_payments, $errorMsg);
	}
	
	/**
	 * 获取支付成功后返回的页面需要显示的信息 
	 * @param array $orderInfo
	 */
	public function getLinksOfPage($tradeInfo = array())
	{
		$bizIdentity 	= $tradeInfo['bizIdentity'];
		$status 		= $tradeInfo['status'];
		$status_label 	= in_array($status, array('PENDING', 'CLOSED')) ? Language::get('pay_failed') : Language::get('pay_successed');
		$payInfo 		= array('payment_code' => $tradeInfo['payment_code'], 'status' => $status, 'status_label' => $status_label, 'Links' =>
							array(array('text' => Language::get('view_trade'), 'link' => Url::toRoute(['deposit/record', 'tradeNo' => $tradeInfo['tradeNo']]))));
			
		// 获取订单信息（以显示不同的连接）
		if(in_array($bizIdentity, array(Def::TRADE_ORDER)))
		{
			!in_array($status, array('PENDING', 'CLOSED')) && $payInfo['status_label'] = Language::get('pay_successed_for_shipped');
			
			// 货到付款的订单
			if(in_array(strtolower($tradeInfo['payment_code']), array('cod'))) {
				$payInfo['status_label'] = Language::get('pay_result_for_cod');
			}
			
			$order = OrderModel::find()->select('order_id')->where(['order_sn' => $tradeInfo['bizOrderId']])->one();
			$payInfo['Links'] = array_merge($payInfo['Links'], array(
				array('text' => Language::get('view_order'), 'link' => Url::toRoute(['buyer_order/view', 'order_id' => $order->order_id])),
				array('text' => Language::get('shopping_continue'), 'link' => Url::toRoute(['default/index']))
			));
		} 
		elseif(in_array($bizIdentity, array(Def::TRADE_RECHARGE)))
		{
			if(!in_array($status, array('PENDING', 'CLOSED'))) {
				$payInfo['status_label'] = $tradeInfo['RETURN_MONEY'] ? Language::get('return_successed') : Language::get('recharge_successed');
			} else $payInfo['status_label'] = $tradeInfo['RETURN_MONEY'] ? Language::get('return_failed') : Language::get('recharge_failed');
			
			if($tradeInfo['RETURN_MONEY']) {
				$payInfo['Links'] = array_merge($payInfo['Links'], array(
					array('text' => Language::get('view_balance'), 'link' => Url::toRoute(['deposit/index'])),
					//array('text' => Language::get('view_return_record'), 'link' => Url::toRoute(['deposit/record', 'tradeNo' =>$tradeInfo['tradeNo']])),
					array('text' => Language::get('view_order'), 'link' => Url::toRoute(['buyer_order/index']))
				));
			}
			else
			{
				$payInfo['Links'] = array_merge($payInfo['Links'], array(
					array('text' => Language::get('view_balance'), 'link' => Url::toRoute(['deposit/index'])),
					array('text' => Language::get('recharge_continue'), 'link' => Url::toRoute(['deposit/recharge']))
				));
			}
		}
		elseif(in_array($bizIdentity, array(Def::TRADE_BUYAPP))) 
		{
			$payInfo['Links'] = array_merge($payInfo['Links'], array(
				array('text' => Language::get('view_balance'), 'link' => Url::toRoute(['deposit/index'])),
				array('text' => Language::get('view_buyapp'), 'link' => Url::toRoute(['appmarket/my']))
			));
		}
		
		return $payInfo;
	}

	/**
	 * 异步通知后的充值订单处理 
	 * @param array $orderInfo
	 * @param array $notify_result
	 */
	public function handleRechargeAfterNotify($orderInfo, $notify_result, $outTradeNo)
	{
		if(!in_array($notify_result['target'], array(Def::ORDER_ACCEPTED, Def::ORDER_FINISHED))) {
			$this->errors = Language::get('trade_status_fail');
			return false;
		}
			
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
		$depopay_type = Business::getInstance('depopay')->build('recharge');
		if(!$depopay_type->respond_notify($orderInfo, $notify_result, $outTradeNo)) {
			return false;
		}
		
		// 如果有充值返金额功能
		if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_RECHARGE))) {
			
			// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
			$depopay_type = Business::getInstance('depopay')->build('rechargegive');
			$depopay_type->rechargegive($orderInfo);
		}
		
		return true;
	}
	
	/**
	 * 异步通知后的购物订单处理 
	 * @param array $orderInfo
	 * @param array $notify_result
	 */
	public function handleOrderAfterNotify($orderInfo, $notify_result)
	{
		if(!in_array($notify_result['target'], array(Def::ORDER_ACCEPTED, Def::ORDER_FINISHED))) {
			$this->errors = Language::get('trade_status_fail');
			return false;
		}
		
		if(empty($orderInfo['tradeList'])) {
			$this->errors = Language::get('trade_info_error');
			return false;
		}
			
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
		$depopay_type = Business::getInstance('depopay')->build('buygoods');
		
		foreach($orderInfo['tradeList'] as $tradeInfo)
		{
			$orderInfo = $tradeInfo['order_info'];
			$result = $depopay_type->respond_notify(array(
				'trade_info' => array('userid' => $tradeInfo['buyer_id'], 'party_id' => $tradeInfo['seller_id'], 'amount' => $tradeInfo['amount']),
				'extra_info' => $orderInfo + array('tradeNo' => $tradeInfo['tradeNo'], 'status' => $tradeInfo['status']),
			));
					
			if(!$result) {
				$this->errors = $depopay_type->errors; // 先把错误存起来
				continue;
			}
			
			// 短信和邮件提醒： 买家已付款通知卖家
			Basewind::sendMailMsgNotify($orderInfo, array(
					'key' => 'toseller_online_pay_success_notify'
				),
				array(
					'key' => 'toseller_online_pay_success_notify',
				)
			);
		}
		return $this->errors ? false : true;
	}

	/**
	 * 异步通知后的购买应用订单处理 
	 * @param array $orderInfo
	 * @param array $notify_result
	 */
	public function handleBuyappAfterNotify($orderInfo, $notify_result)
	{
		if(!in_array($notify_result['target'], array(Def::ORDER_ACCEPTED, Def::ORDER_FINISHED))) {
			$this->errors = Language::get('trade_status_fail');
			return false;
		}
		
		if(empty($orderInfo['tradeList'])) {
			$this->errors = Language::get('trade_info_error');
			return false;
		}
		
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
		$depopay_type = Business::getInstance('depopay')->build('buyapp');
		
		// 目前暂不考虑同时支付多个购买APP的交易，所以循环只有一次
		foreach($orderInfo['tradeList'] as $tradeInfo)
		{
			$order_info = $tradeInfo['order_info'];
			$result = $depopay_type->respond_notify(array(
				'trade_info' => array('userid' => $tradeInfo['buyer_id'], 'party_id' => 0, 'amount' => $tradeInfo['amount']),
				'extra_info' => $order_info + array('tradeNo' => $tradeInfo['tradeNo']),
			));

			if(!$result) {
				$this->errors = $depopay_type->errors; // 先把错误存起来
				continue;
			}
		}
		return $this->errors ? false : true;
	}
}