<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers\my;

use Yii;
use yii\web\Controller;

use common\library\Basewind;
use common\library\Page;
use apiserver\library\Respond;

/**
 * @Id RefundController.php 2019.11.20 $
 * @author yxyc
 */

class RefundController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;
	
	/**
	 * 获取所有我的退款列表数据
	 * @api 接口访问地址: http://api.xxx.com/my/refund/list
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

		$model = new \apiserver\models\RefundForm(['enter' => 'buyer']);
		list($list, $page) = $model->formData($post);
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}
}