<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Plugin;

use apiserver\library\Respond;

/**
 * @Id SmsController.php 2018.10.19 $
 * @author yxyc
 */

class SmsController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 发送手机短信
	 * @api 接口访问地址: http://api.xxx.com/sms/send
	 */
    public function actionSend()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}

		$smser = Plugin::getInstance('sms')->autoBuild();
		if(!$smser) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('msg_send_failure'));
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		// 验证手机号
		if(!Basewind::isPhone($post->phone_mob)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_invalid'));
		}
		
		// 如果是注册，则验证传递的手机号是否唯一
		if(in_array($post->purpose, array('register')))
		{
			if(UserModel::find()->where(['phone_mob' => $post->phone_mob])->exists()) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_existed'), $post->phone_mob);
			}
			$smser->scene = 'touser_register_verify';
		}

		$code = mt_rand(1000, 9999);
		$smser->receiver = $post->phone_mob;
		$smser->templateParams = ['code' => $code];
		
		if(!($codekey = $smser->send())) {
			return $respond->output(Respond::HANDLE_INVALID, $smser->errors);
		}

		Yii::$app->session->set('phone_code', md5($post->phone_mob.$code));
		Yii::$app->session->set('last_send_time_phone_code', Timezone::gmtime());

		return $respond->output(true, Language::get('send_msg_successed'), ['codekey' => $codekey]);
    }
}