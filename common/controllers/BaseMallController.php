<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\library\Basewind;
use common\library\Language;

/**
 * @Id BaseMallController.php 2018.10.20 $
 * @author mosir
 */

class BaseMallController extends Controller
{
	/**
	 * 不启用布局
	 */
	public $layout = false; 

	/**
	 * 关闭CSRF验证
	 */
	public $enableCsrfValidation = false;

	/**
	 * 当前视图
	 */
	public $view    = null;

	/**
	 * 当前访客信息
	 */
	public $visitor = null;

	/**
	 * 允许游客访问的例外Action
	 */
	public $extraAction = null;

	/**
	 * 公用参数
	 */
	public $params 	= null;

	/**
	 * 错误载体
	 */
	public $errors  = null;
	
	/**
	 * 初始化
	 * @var array $visitor 当前的访客信息
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		
		// 安装检测
		Basewind::environment();

		// 获取当前访客信息
		$this->visitor = Basewind::getVisitor();

		// 视图公共参数
		$this->params = ArrayHelper::merge(['visitor' => $this->visitor], [
			'homeUrl'		=> Basewind::homeUrl(),
			'siteUrl'		=> Basewind::siteUrl(),
			'sysversion'	=> Basewind::getVersion(),
			'priceFormat'	=> isset(Yii::$app->params['price_format']) ? Yii::$app->params['price_format'] : '',
			'enablePretty'	=> Yii::$app->urlManager->enablePrettyUrl ? true : false,
			'lang' 			=> Language::find($this->id)
		]);
	}

    /**
	 * 公用操作，避免重复代码
	 * 当两个控制器如：AController和BController需要一个captcha方法,那就可以放到actions
	 * 比如在 site/captcha 的时候，会先在actions方法中找对应请求的 captcha 方法
	 * 如果没有那么就会在控制器中找actionCaptcha
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
				'view' => '@webroot/404.html'
            ],
            'captcha' => [
                'class' 			=> 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' 	=> YII_ENV_TEST ? 'shopwind' : null,
                'maxLength' 		=> 4,
                'minLength' 		=> 4,
                'width' 			=> 108,
				'fontFile'			=> '@common/font/gordon.ttf',
            ],
			'checkCaptcha' => [
				'class' => 'common\actions\CheckCaptchaAction'
			],
			'checkUser' => [
				'class' => 'common\actions\CheckUserAction'
			],
			'checkEmail' => [
				'class' => 'common\actions\CheckEmailAction'
			],
			'checkPhone' => [
				'class' => 'common\actions\CheckPhoneAction'
			],
			'sendCode' => [
				'class'	=> 'common\actions\SendCodeAction'
			],
			'sendEmail' => [
				'class'	=> 'common\actions\SendEmailAction'
			],
			'jslang' => [
				'class' => 'common\actions\JsLangAction',
				'lang'  => $this->params['lang']
			],
			'clearCache' => [
				'class' => 'common\actions\ClearCacheAction'
			]
        ];
    }
	
	/**
	 * 访问权限
	 */
	public function checkAccess($action)
	{
		return true;
	}

	public function accessWarning($params = [])
	{
		$this->params = array_merge($this->params, $params, ['notice' => ['done' => false, 'icon' => 'warning', 'msg' => Language::get('access_limit')]]);
		Yii::$app->response->data = Yii::$app->controller->render('../message.html', $this->params);
		return false;
	}
}