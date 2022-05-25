<?php

return array(
	'deposit'					=>	'预存款',
	'deposit_index'				=>	'资产总览',
	'deposit_config'			=>	'账户配置',
	'deposit_record'			=>	'交易详情',
	'deposit_recharge'			=>	'账户充值',
	'deposit_transfer'			=>	'转账付款',
	'deposit_withdraw'			=>	'提现申请',
	'deposit_frozenlist'		=>  '冻结明细',
	'deposit_indraw'			=>	'充提记录',
	'deposit_tradelist'			=>	'交易记录',
	'deposit_recordlist'		=>	'财务明细',
	'deposit_drawlist'			=>	'提现记录',
	'deposit_rechargelist'		=>	'充值记录',
	'deposit_monthbill'			=>	'月账单下载',
	
	'acount_desc'						=> '资金账户名一旦设置后，将不允许修改，请填写正确的信息',
	'deposit_account'					=> '资金账户',
	'deposit_account_desc'				=> '账号名为邮箱或手机',
	'real_name'							=> '真实姓名',
	'real_name_desc'					=> '填写真实姓名',
	'pay_password'						=> '支付密码',
	'pay_password_desc'					=> '付款时的支付密码',
	'confirm_password'					=> '确认密码',
	'confirm_password_desc'				=> '再次输入支付密码',
	'pay_status_on'						=> '开启余额支付',
	'pay_status_desc'					=> '通过此开关，可以设置您的账户余额资金是否可以用于支付，以保护您的资金安全',
	
	'add_ok'					=>	'添加成功',
	'drop_ok'					=>	'删除成功',
	
	'drop_fail' 				=>	'删除失败',
	
	'email_invalid' 			=>  '请填写电子邮件地址',
	'account_empty'				=>	'预存款账户不能为空',
	'account_invalid' 			=>	'预存款账户必须是邮箱或者手机号',
	'account_exist'				=>	'预存款账户已经存在，请您更换一个',
	'real_name_empty'           => 	'真实姓名不能为空',
	'password_empty'			=>	'账户支付密码不能为空',
	'password_error'			=>	'支付密码错误',
	'password_confirm_error'	=>	'支付密码和重复密码不一致',
	'illegal_param'				=>	'提交非法参数',
	'pay_status_error'			=>	'提交非法参数',
	'pay_status_enable'			=> 	'余额支付已开启',
	'pay_status_closed'			=>	'余额支付已关闭',
	
	'select_bank_error'			=>	'请选择提现到哪张银行卡',
	'bank_not_you'				=>	'此银行卡不是您名下的，请不要提现到该卡',
	'money_error'				=>	'金额不能为空且必须大于0元',
	'money_not_enough'			=>	'您账户余额不足',
	'withdraw_error'			=>	'提现申请提交失败',
	'withdraw_ok_wait_verify'	=>	'提现申请提交成功，请等待管理员审核...',
	'second_submit'				=>	'请不要重复提交',
	
	'mail_captcha_failed'		=>	'邮件验证码错误或者已过期',
	'mail_send_failure'			=>	'邮件验证码发送失败，请联系管理员解决',
	'mail_send_succeed'			=>	'邮件发送成功，请到邮箱：%s 查看您的验证码',
	
	'mail_account_active'		=>	'预存款账户激活邮件',
	'mail_captcha'				=>	'%s提醒：您的验证码为：%s',
	'get_mail_captcha'			=>	'免费获取验证码',
	'get_mail_captcha_again'	=>	'重新获取',
	'miao_hou'					=>	'秒后',
	
	'pay_status_off'			=>	'对不起，您的账户没开启余额支付功能，不能转账',
	'select_account_yourself'	=>	'对不起，您不能向自己的账户转账',
	'select_account_not_exist'	=>	'对不起，您所要转入的账户不存在',
	'transfer_ok'				=>	'转账成功',
	'downloadbill_fail'			=>  '月账单下载失败',
	
	'add_recharge_ok'			=>	'线下充值申请已提交，等待管理员审核',
	'payment_not_available'		=>  '抱歉！您选择的付款方式不可用',
	
	'droprecharge_fail' 		=>  '对不起，该充值记录无法删除',
	'droprecharge_ok' 			=>  '充值记录已成功删除',
	'dropdraw_fail' 			=>  '对不起，该提现记录无法删除',
	'dropdraw_ok' 				=>  '提现记录已成功删除',
	
	'no_data'					=>	'还没有任何记录',
	
	'connecting_pay_gateway'    => '正在连接支付网关, 请稍等...',
	'has_not_account' 			=> '对不起，您还没有配置预存款帐户',
	
	'no_record'					=> '没有交易记录',
	
	'recharge_bank_empty' 		=> '您还没有设置银行卡信息，不能进行线下充值',
	'recharge_money_error' 		=> '充值金额不能为空且必须大于0',
	'withdraw_money_error' 		=> '提现金额不能为空且必须大于0',
	'captcha_empty'				=> '请输入验证码',
	'pending_order_note'		=> '提交订单后，48小时内未支付，交易自动关闭',
	'refund_success_note'		=> '退款成功，退款金额已返还至您的预存款余额中',
	
	'refund_to_buyer'			=> '退款给买家',
	'hasrefund'					=> '有退款',
	
	'cashcard_verify_fail'		=> '充值卡验证失败',
	'cashcard_already_used'		=> '该充值卡已充值',
	'cashcard_already_expired'  => '该充值卡已过期',
	'cashcard_recharge_ok'		=> '您已通过充值卡成功充值%s元',
);