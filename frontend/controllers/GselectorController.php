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

use common\models\GoodsModel;
use common\models\UserModel;
use common\models\GcategoryModel;
use common\models\CategoryGoodsModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Message;
use common\library\Page;

/**
 * @Id GselectorController.php 2018.3.29 $
 * @author mosir
 */

class GselectorController extends \common\controllers\BaseUserController
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
	
	/**
	 * 排除特定Action外，其他需要登录后访问
	 * @param $action
	 * @var array $extraAction
	 */
	public function beforeAction($action)
    {
		$this->extraAction = ['verifycode'];
		return parent::beforeAction($action);
    }
	
	// 选择店铺商品弹出层（可多选）
	public function actionGoods()
    {
		$this->params['sgcategories'] = GcategoryModel::getOptions($this->visitor['store_id']);
		header('Content-Type:text/html;charset=' . Yii::$app->charset);
		return $this->render('../gselector.goods.html', $this->params);
    }
	
	/* 显示运费模板弹出层 */
	public function actionDelivery()
    {
		$this->params['area'] = RegionModel::getProvinceCity();
		header('Content-Type:text/html;charset=' . Yii::$app->charset);
		return $this->render('../gselector.area.html', $this->params);
    }

	/* 显示通用邮箱验证/手机短信验证弹出层 */
	public function actionVerifycode()
	{
		if(!Yii::$app->user->isGuest) {
			$user = ['userid' => $this->visitor['userid'], 'phone_mob' => $this->visitor['phone_mob'], 'email' => $this->visitor['email']];
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
			/* 找回密码情况分析：
				1) 如果本程序注册功能，没有独立的用户名字段（即用户名默认等于手机号），那么通过找回密码只能通过用户名。不能兼容用户名/手机号/邮箱找回密码
				2) 如果本程序注册功能，使用了独立的用户名字段，则可以使用用户名/手机号/邮箱找回密码
				3) 本系统目前暂不做1,2考虑，目前还是仅通过用户名找回密码，作此备忘，以便日后参考
			*/
			$user = UserModel::find()->select('userid,phone_mob,email')->where(['username' => $post->username])->asArray()->one();
		}
		$this->params['user'] = $user;
		$this->params['captcha'] = ['purpose' => 'find_password'];
	
		header('Content-Type:text/html;charset=' . Yii::$app->charset);
		return $this->render('../captcha.form.html', $this->params);
	}
	
	public function actionQuerygoods()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.price,g.default_image,gs.stock')->joinWith('goodsDefaultSpec gs', false)->where(['store_id' => $this->visitor['store_id'], 'if_show' => 1, 'closed' => 0])->orderBy(['goods_id' => SORT_DESC]);
		if($post->keyword) {	
			$query->andWhere(['or', ['like', 'goods_name', $post->keyword],['like', 'brand', $post->keyword],['like', 'cate_name', $post->keyword]]);
		}
		if($post->sgcate_id > 0) {
			$cateIds = GcategoryModel::getDescendantIds($post->sgcate_id, $this->visitor['store_id']);
			$goodsIds = CategoryGoodsModel::find()->select('goods_id')->where(['in', 'cate_id', $cateIds])->indexBy('goods_id')->asArray()->all();
			$query->andWhere(['in', 'g.goods_id', array_keys($goodsIds)]);	
        }
		
		$page = Page::getPage($query->count(), 5, true);
		$goodsList = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($goodsList as $key => $goods) {
            $goodsList[$key]['goods_name'] = htmlspecialchars($goods['goods_name']);
			$goods['default_image'] || $goodsList[$key]['default_image'] = Yii::$app->params['default_goods_image'];
        }
		return Message::result(['goodsList' => $goodsList, 'pagination' => Page::formatPage($page)]);
	}
}