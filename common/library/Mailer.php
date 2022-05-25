<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;

use common\components\queue\SendEmail;

/**
 * @Id Mailer.php 2018.4.1 $
 * @author mosir
 */

class Mailer
{
	public $compose = null;
	public $errors = null;
	
	public function __construct($compose = null)
	{
		$this->compose = $compose;
	}
	
	public function send($async = true)
	{
		// 异步发送
		if($async && Yii::$app->params['mailer']['async']) {
			return Yii::$app->queue->push(new SendEmail(['compose' => $this->compose]));
		}
		
		// 同步发送
		return $this->compose->send();
	}
	
	public function compose()
	{
		$admin = Yii::$app->params['mailer']['admin'] ? Yii::$app->params['mailer']['admin'] : 'ShopWind';
		
		Yii::$app->set('mailer', [
      		'class' => 'yii\swiftmailer\Mailer',
         	'viewPath' => '@common/mail',
			'useFileTransport' => false,
          	'transport' => [
        		'class' => 'Swift_SmtpTransport',
           		'host' => Yii::$app->params['mailer']['host'],
          		'username' => Yii::$app->params['mailer']['username'],
            	'password' => Yii::$app->params['mailer']['password'],
             	'port' => Yii::$app->params['mailer']['port'],
           		'encryption' => 'tls',
     		],
			'messageConfig'=> [  
				'charset' => Yii::$app->charset,
				'from' => [Yii::$app->params['mailer']['email'] => $admin]
			], 
  		]);
		
		return new self(Yii::$app->mailer->compose());
	}
	
	public function setTo($toEmail)
	{
		$this->compose->setTo($toEmail);
		return new self($this->compose);
	}
	
	public function setSubject($subject)
	{
		
		$this->compose->setSubject(addslashes($subject));
		return new self($this->compose);
	}
	
	public function setHtmlBody($content)
	{
		$this->compose->setHtmlBody(addslashes($content));
		return new self($this->compose);
	}
}