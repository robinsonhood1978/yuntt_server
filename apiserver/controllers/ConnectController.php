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
use yii\helpers\ArrayHelper;

use common\models\BindModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Plugin;
use common\library\Timezone;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id ConnectController.php 2018.5.31 $
 * @author mosir
 */

class ConnectController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 账号联合登录
	 * @api 接口访问地址: http://api.xxx.com/connect/login
	 */
	public function actionLogin()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$connect = Plugin::getInstance('connect')->build($post->logintype, $post);
		$result = $connect->login(false);

		return $respond->output(true, null, $result);
    }
}