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

use common\library\Basewind;
use common\library\Message;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id SendEmailAction.php 2018.3.7 $
 * @author mosir
 */
 
class SendEmailAction extends Action
{
	/**
     * Runs the action.
	 * 
	 * 发送电子邮件
     */
    public function run()
    {
		if(Yii::$app->request->isPost)
		{
			// 邮件发送状态关闭了
			if(!Yii::$app->params['mailer']['status']) {
				return Message::warning(Language::get('email_server_disabled'));
			}

			$purpose 	= trim(Yii::$app->request->post('purpose', ''));
			$email 		= trim(Yii::$app->request->post('email', ''));
			$userid 	= intval(Yii::$app->request->post('userid', 0));
			
			// 如果是找回密码，则通过传递的userid 的值，找出Email，避免POST过程中被串改
			if(in_array($purpose, array('find_password')))
			{
				$user = UserModel::find()->select('email')->where(['userid' => $userid])->asArray()->one();
				if(!$user || !$user['email']) {
					return Message::warning(Language::get('email_no_existed'));
				} else $email = $user['email'];  // 重新赋值，不允许串改
			}
			
			// 如果是第三方登录绑定，则验证传递的电子邮箱是否唯一
			if(in_array($purpose, array('member_bind')))
			{
				if(UserModel::find()->select('userid')->where(['email' => $email])->asArray()->one()) {
					return Message::warning(Language::get('email_exist'));
				}
			}
			
			$code = mt_rand(1000, 9999);
			$mailer = Basewind::getMailer('touser_send_code', ['user' => Yii::$app->controller->visitor, 'word' => $code]);
			
			if(!$mailer || !$mailer->setTo($email)->send()) {
				return Message::warning(Language::get('mail_send_failure'));
			}

			Yii::$app->session->remove('phone_code');
			Yii::$app->session->remove('last_send_time_phone_code');
			Yii::$app->session->set('email_code', md5($email.$code));
			Yii::$app->session->set('last_send_time_email_code', Timezone::gmtime());

			return Message::display(Language::get('captcha_send_succeed'));
      	}
	}
}