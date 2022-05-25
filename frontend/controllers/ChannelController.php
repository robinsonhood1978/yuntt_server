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
use common\models\ChannelModel;
use common\models\NavigationModel;

use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id ChannelController.php 2018.9.10 $
 * @author mosir
 */

class ChannelController extends \common\controllers\BaseMallController
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
		$id = Yii::$app->request->get('id', 0);
		if(!$id || !($channel = ChannelModel::find()->where(['cid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_channel'));
		}
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
		
		$this->params['page'] = Page::seo(['title' => $channel['title']]);
		return $this->render('../channel.style'.$channel['style'].'_'.$id.'.html', $this->params);
	}
}