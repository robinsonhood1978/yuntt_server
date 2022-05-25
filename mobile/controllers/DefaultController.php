<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Resource;
use common\library\Page;
use common\library\Weixin;

/**
 * @Id DefaultController.php 2018.9.13 $
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}

    public function actionIndex()
    {
		$this->params['index'] = true;
		
		$this->params['page'] = Page::seo();
        return $this->render('../index.html', $this->params);
    }
}