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

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use apiserver\library\Respond;

/**
 * @Id CashcardController.php 2018.10.15 $
 * @author yxyc
 */

class CashcardController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取用户充值卡列表
	 * @api 接口访问地址: http://api.xxx.com/cashcard/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		
		$model = new \frontend\models\My_cashcardForm();
		list($list, $page) = $model->formData($post, $post->page_size, false, $post->page);
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 绑定充值卡并充值到余额
	 * @api 接口访问地址: http://api.xxx.com/cashcard/bind
	 */
    public function actionBind()
    {
		// 验证签名
		 $respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		$model = new \frontend\models\DepositCardrechargeForm();
		if(!$model->submit($post)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		
		return $respond->output(true, null, ['tradeNo' => $model->tradeNo]);
	}
}