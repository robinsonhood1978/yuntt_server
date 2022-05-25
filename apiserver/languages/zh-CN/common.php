<?php
return array(
	'request_ok'			=> '请求成功！',
	'handle_fail'			=> '操作失败！',
	'handle_exception'		=> '业务处理异常',
	'appid_empty' 			=> 'appid不能为空',
	'appid_invalid' 		=> 'appid非法',
	'sign_empty'  			=> '签名（sign）值不能为空',
	'sign_invalid'			=> '签名（sign）不正确',
	'signtype_invalid' 		=> '目前只支持MD5签名',
	'timestamp_empty'   	=> '时间戳（timestamp）不能为空',
	'timestamp_invalid'		=> '请求已失效（timestamp已过期）',
	'user_invalid'			=> '请先登录',
	'login_please' 			=> '请先登录',
	'token_invalid'			=> 'TOKEN无效',
	'token_expire'			=> 'TOKEN已过期',
	
	'no_such_user'			=> '用户不存在',
	'username_existed'		=> '该用户名已存在',
	'phone_mob_required' 	=> '手机号码不能为空',
	'phone_mob_invalid' 	=> '请输入正确的手机号',
	'phone_mob_existed'		=> '该手机号已存在',
	'email_required' 		=> '邮箱不能为空',
	'email_invalid' 		=> '请输入正确的邮箱',
	'email_existed'			=> '该邮箱已存在',
	'on_sush_item'			=> '查询的数据不存在',
	
	'express'				=> '快递',
	'ems'					=> 'EMS',
	'post'					=> '平邮',
	
	'stock'     			=> '库存',
	'defray'				=> '支付',
	'transfer'				=> '转账',
	'recharge'				=> '充值',
	
	// 支付类型
	'SHIELD'			=> '担保交易',
	'INSTANT'			=> '即时到账',
	'COD'				=> '货到付款',
	
	// 交易类型
	'PAY'				=> '在线支付',
	'TRANSFER'			=> '转账',
	'SERVICE'			=> '服务费',
	'WITHDRAW'			=> '提现',
	'RECHARGE'			=> '充值',
	'RECHARGECARD'		=> '充值卡',
	
	// 针对交易的状态
	'TRADE_PENDING'				=> '等待买家付款',
	'TRADE_ACCEPTED'    		=> '等待卖家发货',
	'TRADE_SUBMITTED'			=> '货到付款待发货',
	'TRADE_SHIPPED'				=> '卖家已发货',
	'TRADE_SUCCESS'				=> '交易完成',
	'TRADE_CLOSED'				=> '交易关闭',
	'TRADE_WAIT_ADMIN_VERIFY' 	=> '等待系统审核',

	
	// 针对退款的状态
	'REFUND_SUCCESS'				=> '退款成功',
	'REFUND_CLOSED'					=> '退款关闭',
	'REFUND_WAIT_SELLER_AGREE'		=> '买家申请退款，等待卖家同意',
	'REFUND_SELLER_REFUSE_BUYER'	=> '卖家拒绝退款，等待买家修改中',
	'REFUND_WAIT_SELLER_CONFIRM'	=> '退款申请等待卖家确认中',

	'has_apply_refund'			=> '已申请退款',
	'party_apply_refund'		=> '对方已申请退款',
	'trade_refund_return'		=> '交易退款',
	'trade_refund_pay'			=> '交易付款',
	
	'deposit'				=> '余额支付',
	'alipay'				=> '支付宝',
	'alipay_wap'			=> '手机支付宝',
	'alipay_app'			=> '支付宝APP支付',
	'tenpay' 				=> '财付通',
	'tenpay_wap' 			=> '手机财付通',
	'unionpay'  			=> '中国银联',
	'wxpay'  				=> '微信支付',
	'wxnativepay'  			=> '微信扫码支付',
	'wxh5pay' 				=> '微信H5支付',
	'wxapppay'             	=> '微信APP支付',
	'cod'					=> '货到付款',
	
	'sms_buy' 				=> '您店铺下了一个新订单，订单号为[%s]，请联系买家及时付款',
	'sms_send' 				=> '您的订单[%s]，卖家[%s]已经发货，请及时查收！',
	'sms_check' 			=> '您的订单[%s]，买家[%s]已经确认！',
	'sms_pay' 				=> '您的订单[%s]，买家[%s]已经付款！',
	'msg_send_failure'		=> '短信发送失败',
	'send_msg_successed'	=> '短信发送成功',

	'phone_code_check_failed'   	=> '手机验证码错误或已失效',
	'phone_code_check_timeout' 		=> '短信验证码已经过期',
	'send_limit_frequency_one_time' => '请过%s秒后重试',
	'send_limit_frequency_five_time'=> '发送太频繁请稍后再试',
	'send_limit_frequency_daytimes' => '同一号码每天最多发送%s次短信',
);
