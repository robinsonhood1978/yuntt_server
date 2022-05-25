<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\sms\alidayu;

use yii;

use common\library\Language;
use common\plugins\BaseSms;

use Flc\Dysms\Client;
use Flc\Dysms\Request\SendSms;

/**
 * @Id alidayu.plugin.php 2018.6.5 $
 * @author mosir
 */

class Alidayu extends BaseSms
{
    /**
	 * 网关地址
	 * @var string $gateway
	 */
    protected $gateway = 'https://dysmsapi.aliyuncs.com';
    
	/**
     * 短信实例
	 * @var string $code
	 */
    protected $code = 'alidayu';

    /**
	 * 构造函数
	 */
	public function __construct()
	{
        parent::__construct();
    }
    
    /**
	 * 发送短信
     * @param bool $valid 发送频次等的校验，如果是系统发送的短信，可以适当的不做该校验以确保发送成功
	 */
	public function send($valid = true)
	{
        if(!$this->verify()) {
            return false;
        }
      
        if($valid === true && !$this->validSend()) {
            return false;
        }
        
        // 发送的短信信息校验
        if($this->validData() == false) {
            return false;
        }
  
        $result = $this->submit();
        $code = strtoupper($result->Code);
        if($code == 'OK') {
            $codekey = $this->insert(1);
            return $codekey;
        }
        
        $this->errors = $result->Message;
        $this->insert(0, $this->errors);
        return false;
    }

    /**
     * 测试短信发送（阿里接口不支持，兼容处理）
     */
    public function testsend($content = '') {
        $this->errors = "Testing is not supported";
        return false;
    }
    
    /**
     * 执行短信发生
     */
    private function submit()
    {
        $config = [
            'accessKeyId'    => $this->config['AppKey'],
            'accessKeySecret' => $this->config['AppScrect']
        ];

        $sendSms = new SendSms;
        $sendSms->setPhoneNumbers($this->receiver);
        $sendSms->setSignName($this->signName);
        $sendSms->setTemplateCode($this->templateId);
        $sendSms->setTemplateParam($this->templateParams);
        //$sendSms->setOutId('demo');
        
        $client = new Client($config);
        return $client->execute($sendSms);
    }

    /**
     * 检测是否配置
     * @var boolean $force 是否验证短信模板内容
     */
    public function verify($force = true)
    {
        if(!$this->config['AppKey']) {
            $this->errors = Language::get('The "AppKey" property must be set');
            return false;
        }
        if(!$this->config['AppScrect']) {
            $this->errors = Language::get('The "AppScrect" property must be set');
            return false;
        }

        // 如果是验证非具体短信场景，可以不用验证短信模板
        // 比如某个地方仅仅需要判断密钥是否配置，从而进行开关控制
        if(!$force) {
            return true;
        }

        // 传递具体短信场景参数，则验证短信模板
        if(($template = $this->getTemplate()) === false) {
            return false;
        }
        if(!$template || empty($template->content)) {
            $this->errors = Language::get('The "content" property must be set');
            return false;
        }
        if(empty($template->templateId)) {
            $this->errors = Language::get('The "templateId" property must be set');
            return false;
        }
        if(empty($template->signName)) {
            $this->errors = Language::get('The "signName" property must be set');
            return false;
        }

        // 此处为必须赋值，避免无法发送短信
        $this->templateId = $template->templateId;
        $this->signName = $template->signName;
        $this->content = $this->getContent($template);

        return true;
    }
}

