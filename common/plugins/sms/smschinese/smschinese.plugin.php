<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\sms\smschinese;

use yii;

use common\models\MsgTemplateModel;

use common\library\Basewind;
use common\library\Language;
use common\plugins\BaseSms;

/**
 * @Id smschinese.plugin.php 2018.6.5 $
 * @author mosir
 */

class Smschinese extends BaseSms
{
    /**
	 * 网关地址
	 * @var string $gateway
	 */
    protected $gateway = 'http://utf8.api.smschinese.cn';
    
	/**
     * 短信实例
	 * @var string $code
	 */
    protected $code = 'smschinese';

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
        
        // 大于0说明发送成功，$result即为发送的短信条数（当短信内容过长，会分成2条来发送）
        // 如果小于0，$result 代表错误代码
        $result = $this->submit();
        if($result > 0) {
            $codekey = $this->insert($result);
            return $codekey;
        }

        $this->errors = $this->getMessage($result);
        $this->insert(0, $this->errors);
        return false;
    }

    /**
     * 测试短信发送（仅做测试用，不要用作正式场合）
     */
    public function testsend($content = '') {
        $this->content = $content;
        $result = $this->submit();
        if($result > 0) {
            return true;
        }

        $this->errors = $this->getMessage($result);
        return false;
    }
    
    /**
     * 执行短信发生
     */
    private function submit()
    {
        $url = $this->gateway.'/?Uid='.$this->config['uid'].'&Key='.$this->config['key'] .
			    '&smsMob=' . $this->receiver . '&smsText=' . urlencode($this->content);

        return Basewind::curl($url);
    }

    /**
     * 检测是否配置
     * @var boolean $force 是否验证短信模板内容
     */
    public function verify($force = true)
    {
        if(!$this->config['uid']) {
            $this->errors = Language::get('The "uid" property must be set');
            return false;
        }
        if(!$this->config['key']) {
            $this->errors = Language::get('The "key" property must be set');
            return false;
        }

        // 如果是验证非具体短信创建，可以不用验证短信模板
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

        // 此处为必须赋值，避免无法发送短信。ID和签名该短信接口不需要赋值
        //$this->templateId = $template->templateId;
        //$this->signName = $template->signName;
        $this->content = $this->getContent($template);
 
        return true;
    }

    /**
     * 网建的短信模板内容不需要审核，因此如果没有配置模板，可以用其他平台的模板
     * @var string $scene
     */
    public function getTemplate() 
	{
        if(($template = parent::getTemplate())) {
            return $template;
        }

        // 有异常（非空的情况）
        if($template === false) {
            return false;
        }
        
        // 使用其他平台的短信模板
		$query = MsgTemplateModel::find()->where(['and', ['scene' => $this->scene], ['!=', 'content', '']])->orderBy(['id' => SORT_DESC])->one();
        return $query ? $query : null;
    }
}

