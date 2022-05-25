<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\GcategoryModel;
use common\models\NavigationModel;

use common\library\Resource;
use common\library\Page;

/**
 * @Id DefaultController.php 2018.3.1 $
 * @author mosir
 */

class DefaultController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'), [
			'navs'	=> NavigationModel::getList()
		]);
	}
	
	public function actionIndex()
	{
		// 首页标识
		$this->params['index'] = true;

		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();

		// 导入视图资源文件
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js,cart.js');

		$this->params['page'] = Page::seo();
        return $this->render('../index.html', $this->params);
	}
}
