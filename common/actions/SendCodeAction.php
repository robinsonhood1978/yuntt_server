<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\actions;

use Yii;
use yii\base\Action;

use common\models\UserModel;

use common\library\Message;
use common\library\Language;
use common\library\Timezone;
use common\library\Plugin;

/**
 * @Id SendCodeAction.php 2018.3.6 $
 * @author mosir
 */

class SendCodeAction extends Action
{
	public function init()
    {
		$this->controller->enableCsrfValidation = true;
    }
	
	/**
	 * 发送手机验证
     */
    public function run()
    {
		if(Yii::$app->request->isPost)
		{
			$purpose 	= trim(Yii::$app->request->post('purpose', ''));
			$phone_mob 	= trim(Yii::$app->request->post('phone_mob', ''));
			$userid 	= intval(Yii::$app->request->post('userid', 0));

			$smser = Plugin::getInstance('sms')->autoBuild();
			if(!$smser) {
				return Message::warning(Language::get('msg_send_failure'));
			}
			
			// 如果是找回密码，则通过传递的userid的值，找出手机号，避免POST过程中被串改
			if(in_array($purpose, array('find_password')))
			{
				// 暂时不做权限控制
				// TODO...
				$query = UserModel::find()->select('phone_mob')->where(['userid' => $userid])->one();
				if(!$query || !$query->phone_mob) {
					return Message::warning(Language::get('phone_mob_no_existed'));
				}
				$phone_mob = $query->phone_mob;  // 重新赋值，不允许串改
				$smser->scene = 'touser_findpassword_verify';
			}
			
			// 如果是注册，则验证传递的手机号是否唯一
			if(in_array($purpose, array('register')))
			{
				// 暂时不做权限控制
				// TODO...
				if(UserModel::find()->where(['phone_mob' => $phone_mob])->exists()) {
					return Message::warning(Language::get('phone_mob_existed'));
				}
				$smser->scene = 'touser_register_verify';
			}

			$code = mt_rand(1000, 9999);
			$smser->receiver = $phone_mob;
			$smser->templateParams = ['code' => $code];
			
			if(!$smser->send()) {
				return Message::warning($smser->errors);
			}

			Yii::$app->session->remove('email_code');
			Yii::$app->session->remove('last_send_time_email_code');
			Yii::$app->session->set('phone_code', md5($phone_mob.$code));
			Yii::$app->session->set('last_send_time_phone_code', Timezone::gmtime());

			return Message::display(Language::get('send_msg_successed'));
      	}
	}
}