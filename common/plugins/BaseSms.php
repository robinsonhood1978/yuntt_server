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

use common\models\UserModel;
use common\models\MsgModel;
use common\models\MsgLogModel;
use common\models\MsgTemplateModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id BaseSms.php 2018.6.4 $
 * @author mosir
 */
 
class BaseSms extends BasePlugin
{
	/**
	 * 短信插件系列
	 * @var string $instance
	 */
	protected $instance = 'sms';

	/**
	 * 发送短信的用户ID
	 * 0代表是系统发送
	 */
	public $sender = 0;

	/**
	 * 接收短信的手机号
	 */
	public $receiver;

	/**
	 * 短信内容
	 * 建议通过模板ID来获取短信内容，不要直接赋值
	 */
	protected $content;

	/**
	 * 短信场景
	 */
	public $scene = 'touser_sendcode_verify';

	/**
	 * 短信模板变量
	 */
	public $templateParams;

	/**
	 * 短信签名
	 */
	public $signName;

	/**
	 * 一天内允许某个手机号发送的短信数量
	 */
	private $dayTimes = 10;

	/**
	 * 半小时内允许某个手机号发送多少条短信
	 */
	private $semihourTimes = 5;

	/**
	 * 某个手机号每次发送短信间隔多少秒
	 */
	private $interval = 120;

	/**
	 * 插入发送短信的记录
	 */
	public function insert($quantity = 1, $message = null)
	{
		$model = new MsgLogModel();
		$model->code = $this->code;
		$model->userid = $this->sender ? $this->sender : 0;
		$model->receiver = $this->receiver;
		$model->content = $this->content;
		$model->quantity = $quantity > 0 ? $quantity : 0;
		$model->status = $quantity > 0 ? 1 : 0;
		$model->message = $message ? $message : '';
		$model->add_time = Timezone::gmtime();
		$model->codekey = md5(md5($this->code.$this->sender.$this->receiver.$this->scene.$this->code.mt_rand()));
		$model->verifycode = $this->templateParams['code'];

		if(!$model->save(false)) {
			$this->errors = $model->errors;
			return false;
		}
		return $model->codekey;
	}

	/**
	 * 验证发送的内容是否合法
	 * 如果是用户发送（非系统发送），还验证用户是否有发送的权限
	 */
	public function validData()
	{
		// 短信内容为空
		if(!$this->content) {
			$this->errors = $this->getMessage(-42);
			return false;
		}

		// 如果接收者手机号为空，则从用户ID读取手机号
		if(!$this->receiver) {
			if(!$this->sender) {
				$this->errors = $this->getMessage(-41);
				$this->insert(0, $this->errors);
				return false;
			}
			
			$query = UserModel::find()->select('phone_mob')->where(['userid' => $this->sender])->one();
			if(!$query) {
				$this->errors = $this->getMessage(-1);
				return false;
			}
			$this->receiver = $query->phone_mob;
		}

		if(!Basewind::isPhone($this->receiver)) {
			$this->errors = $this->getMessage(-4);
			$this->insert(0, $this->errors);
			return false;
		}

		// 如果是用户发送短信，则检查用户是否有发送短信的权限
		if($this->sender > 0 && $this->scene)
		{
			$query = MsgModel::find()->where(['userid' => $this->sender])->one();
			
			// 短信功能被关闭
			if(!$query->state) {
				$this->errors = $this->getMessage(-12);
				$this->insert(0, $this->errors);
				return false;
			}
			
			// 没有短信了
			if($query->num <= 0) {
				$this->errors = $this->getMessage(-3);
				$this->insert(0, $this->errors);
				return false;
			}
			
			// 没有发送短信的权限
			if(empty($query->functions)) {
				$this->errors = $this->getMessage(-13);
				$this->insert(0, $this->errors);
				return false;
			}
			
			$funs = explode(',', $query->functions);
			if(!in_array($this->scene, $funs) || !in_array($this->scene, $this->getFunctions())) {
				$this->errors = $this->getMessage(-13);
				$this->insert(0, $this->errors);
				return false;
			}
		}

		return true;
	}

	/**
	 * 验证是否可以发送短信
	 * 发送频率、发送时间间隔控制
	 */
	public function validSend() 
	{
		if(!$this->receiver) {
			$this->errors = Language::get('phone_empty');
			return false;
		}

		// 当天开始时间戳
		$dayBegin = strtotime(Timezone::localDate("Y-m-d 00:00:00", Timezone::gmtime()));
		
		// 当天已经成功发送的短信条数
		$query = MsgLogModel::find()->select('add_time')->where(['status' => 1, 'type' => 0, 'code' => $this->code])->andWhere(['receiver' => $this->receiver])
					->andWhere(['>=', 'add_time', $dayBegin])->orderBy(['add_time' => SORT_DESC]);
					
		$count = $query->count();
		$record = $query->one();
		
		// 当天没有发送过短信
		if($count <= 0) return true;

		// 每次发送短信时间间隔控制
		if(Timezone::gmtime() < $record->add_time + $this->interval) {
			$this->errors = sprintf(Language::get('send_limit_frequency_one_time'), $this->interval);
			return false;
		}

		// 半小时内发送数量控制
		if(($count % $this->semihourTimes == 0) && (Timezone::gmtime() < $record->add_time + 1800)) {
			$this->errors = sprintf(Language::get('send_limit_frequency_five_time'), $this->semihourTimes);
			return false;
		}

		// 一天内发送数量控制
		if($count >= $this->dayTimes) {
			$this->errors = sprintf(Language::get('send_limit_frequency_daytimes'), $this->dayTimes);
			return false;
		}
	
		return true;
	}

	/**
	 * 短信发送的时机
	 * @param bool $all 是否获取全部，包含平台发的验证类短信
	 */
	public function getFunctions($all = false)
    {
		// 开放给用户配置的短信场景，可以考虑收费发送的
        $array = [       
        	'toseller_new_order_notify', 			// 买家已下单通知卖家
			'toseller_online_pay_success_notify',  	// 买家已付款通知卖家   
       		'tobuyer_shipped_notify', 				// 卖家已发货通知买家   
			'toseller_finish_notify', 				// 买家已确认通知卖家
			'toseller_refund_apply_notify', 		// 买家退款申请，通知卖家
			'tobuyer_refund_agree_notify', 			// 卖家同意退款成功，通知买家
		];
		if($all) {
			$array = array_merge([
					'touser_sendcode_verify',		// 用户发送验证端
					'touser_register_verify',		// 用户注册验证短信
					'touser_findpassword_verify'	// 用户找回密码验证短信
				],
				$array
			);
		}
			
        return $array;
	}

	/**
     * 获取短信模板
     * @var string $scene 短信场景
	 * @return ActiveRecord|false|null $query
     */
    public function getTemplate()
	{
		$all = $this->getFunctions(true);
		if(!in_array($this->scene, $all)) {
			$this->errors = $this->getMessage(-13);
			return false;
		}

		$query = MsgTemplateModel::find()->where(['code' => $this->code, 'scene' => $this->scene])->one();
        return $query ? $query : null;
	}

	 /**
     * 获取发送短信的内容
     * @param bool 是否格式化短信模板内容变量
     */
    public function getContent($template, $format = true)
    {
       if(!$template || empty($template->content)) {
            return false;
        }

        // 替换掉模板变量
        return $format ? $this->formatContent($template->content) : $template->content;
    }

	/**
     * 替换短信模板内容里面的变量
	 * @param string $content
     */
    public function formatContent($content)
    {
        if(!$this->templateParams || !is_array($this->templateParams)) {
            return $content;
        }

        // 短信模板内容的参数替换
        foreach($this->templateParams as $key => $value) {
            $content = str_replace("{$key}", (string)$value, $content);
            $content = str_replace("\$", '', $content);
            $content = str_replace(array('{', '}'), array('',''), $content);
        }

        return $content;
	}
	
	/**
	 * 解决微信端（小程序）session无法同步的问题
	 */
	public function setSessionByCodekey($verifycodekey = '')
	{
		$model = MsgLogModel::find()->select('receiver,verifycode,add_time')->where(['codekey' => $verifycodekey])->one();
		if($verifycodekey && $model) {
			Yii::$app->session->set('phone_code', md5($model->receiver.$model->verifycode));
			Yii::$app->session->set('last_send_time_phone_code', $model->add_time + 120);
		}
	}

	public function getMessage($code)
	{
		if($code > 0) {
			return '发送成功';
		}
		if($code == -1) {
			return '没有该用户账户';
		}
		if($code == -2) {
			return '接口密钥不正确';
		}
		if($code == -3) {
			return '短信数量不足';
		}
		if($code == -4){
			return '手机号格式不正确';
		}
		if($code == -6) {
			return 'IP限制';
		}
		if($code == -11) {
			return '该用户被禁用';
		}
		// 本站的短信开关CODE（非短信平台）
		if($code == -12) {
			return '请先开启短信功能';
		}
		// 本站的短信权限CODE（非短信平台）
		if($code == -13) {
			return '无法发送该短信，请检查权限';
		}
		if($code == -14) {
			return '短信内容出现非法字符';
		}
		elseif($code == -21) {
			return '密钥不正确';
		}
		if($code == -41) {
			return '手机号码为空';
		}
		if($code == -42) {
			return '短信内容为空';
		}
		if($code == -51) {
			return '短信签名不正确';
		}

		return '未知错误';
	} 
}