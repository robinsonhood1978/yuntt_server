<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;


use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id PromoteController.php 2018.9.10 $
 * @author mosir
 */

class PromoteController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}

	public function actionIndex()
	{
		$list = Plugin::getInstance('promote')->build()->getList();

		// 归类处理
		$result = [];
		foreach($list as $key => $value) {
			$result[$value['category'] ? $value['category'] : 'store'][] = $value;
		}
		$this->params['result'] = $result;

		$this->params['page'] = Page::seo(['title' => Language::get('promote_list')]);
		return $this->render('../promote.index.html', $this->params);
	}
}
