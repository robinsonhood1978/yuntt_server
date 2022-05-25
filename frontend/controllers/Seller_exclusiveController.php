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
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Promotool;

/**
 * @Id Seller_exclusiveController.php 2018.5.23 $
 * @author mosir
 */

class Seller_exclusiveController extends \common\controllers\BaseSellerController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('user'));
	}

    public function actionIndex()
    {
		if(!Yii::$app->request->isPost)
		{
			$exclusiveTool = Promotool::getInstance('exclusive')->build(['store_id' => $this->visitor['store_id']]);
			if(($message = $exclusiveTool->checkAvailable(true, false)) !== true) {
				$this->params['tooldisabled'] = $message;
			}
			$this->params['exclusive'] = $exclusiveTool->getInfo();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('seller_exclusive'), Url::toRoute('seller_exclusive/index'), Language::get('exclusive_index'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('seller_exclusive', 'exclusive_index');

			$this->params['page'] = Page::seo(['title' => Language::get('exclusive_index')]);
        	return $this->render('../seller_exclusive.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post('exclusive'), true);

			$post->status = intval(Yii::$app->request->post('status'));
			$model = new \frontend\models\Seller_exclusiveForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}		
			return Message::display(Language::get('handle_ok'));
		}		
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'exclusive_index',
                'url'  => Url::toRoute(['seller_exclusive/index']),
            )
        );

        return $submenus;
    }
}