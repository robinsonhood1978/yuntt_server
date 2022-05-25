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

use common\models\CollectModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id My_favoriteController.php 2018.6.20 $
 * @author mosir
 */

class My_favoriteController extends \common\controllers\BaseUserController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(in_array($post->type, ['goods', ''])) {
			return $this->listCollectGoods();
		} else {
			return $this->listCollectStore();
		}
	}
	
	public function actionAdd()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(in_array($post->type, ['goods', ''])) {
			return $this->addCollectGoods();
		} else {
			return $this->addCollectStore();
		}
	}
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), 2);
		
        if (!$post->item_id || !in_array($post->type, ['goods', 'store'])) {
            return Message::warning(Language::get('no_such_collect_item'));
        }
		if(!CollectModel::deleteAll(['and', ['in', 'item_id', explode(',', $post->item_id)], ['type' => $post->type, 'userid' => Yii::$app->user->id]])) {
			return Message::warning(Language::get('drop_collect_failed'));
		}
        return Message::display(Language::get('drop_collect_successed'));
    }
	
	private function listCollectGoods()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\My_favoriteGoodsForm();
		list($goodsList, $page) = $model->formData($post, $post->pageper);
		$this->params['goodsList'] = $goodsList;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_favorite'), Url::toRoute('my_favorite/index'), Language::get('collect_goods'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_favorite', 'collect_goods');	

		$this->params['page'] = Page::seo(['title' => Language::get('my_favorite')]);
		return $this->render('../my_favorite.goods.html', $this->params);
	}
	
	private function listCollectStore()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\My_favoriteStoreForm();
		list($storeList, $page) = $model->formData($post, $post->pageper);
		$this->params['storeList'] = $storeList;
		$this->params['pagination'] = Page::formatPage($page);
	
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_favorite'), Url::toRoute('my_favorite/index'), Language::get('collect_store'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_favorite', 'collect_store');	

		$this->params['page'] = Page::seo(['title' => Language::get('my_favorite')]);
		return $this->render('../my_favorite.store.html', $this->params);
	}
	
	private function addCollectGoods()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['item_id']);
		
		$model = new \frontend\models\My_favoriteGoodsForm();
		if(!$model->addCollect($post)) {
			return Message::warning($model->errors);
		}
        return Message::display(Language::get('collect_goods_ok'));
    }
	
	private function addCollectStore()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['item_id']);
		
        $model = new \frontend\models\My_favoriteStoreForm();
		if(!$model->addCollect($post)) {
			return Message::warning($model->errors);
		}
        return Message::display(Language::get('collect_store_ok'));
    }

	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'collect_goods',
                'url'   => Url::toRoute(['my_favorite/index']),
            ),
			array(
                'name'  => 'collect_store',
                'url'   => Url::toRoute(['my_favorite/index', 'type' => 'store']),
            ),
        );

        return $submenus;
    }
}