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

use common\models\DistributeSettingModel;
use common\models\GoodsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Promotool;
use common\library\Page;

/**
 * @Id DistributeController.php 2018.12.15 $
 * @author luckey
 */

class DistributeController extends \common\controllers\BaseSellerController
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
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.price,g.if_show,g.closed,gs.stock')->joinWith('goodsDefaultSpec gs', false)->where(['store_id' => $this->visitor['store_id'], 'g.closed' => 0, 'g.if_show' => 1]);
		if($post->sort) {
			$query->orderBy([$post->sort => (strtolower($post->orderby) == 'asc') ? SORT_ASC : SORT_DESC]);
		} else $query->orderBy(['goods_id' => SORT_DESC]);
		
		$page = Page::getPage($query->count(), 16);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $goods) {
			$goods['default_image'] || $list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			if($setting = DistributeSettingModel::find()->where(['item_id' => $goods['goods_id'], 'type' => 'goods', 'enabled' => 1])->one()){
				$list[$key]['enabled'] = 1;
				$list[$key]['ratio1'] = $setting['ratio1'];
				$list[$key]['ratio2'] = $setting['ratio2'];
				$list[$key]['ratio3'] = $setting['ratio3'];
			}
        }
		$this->params['goods_list'] = $list;
		$this->params['pagination'] = Page::formatPage($page);

		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js, dialog/dialog.js',
            'style' => 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);

		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('distribute'), Url::toRoute('distribute/index'), Language::get('distribute_goods'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('distribute', 'distribute_goods');

		$this->params['page'] = Page::seo(['title' => Language::get('distribute')]);
        return $this->render('../distribute.index.html', $this->params);
	}
	
	public function actionEdit()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

        if(!$post->goods_id || !($list = GoodsModel::find()->select('goods_id,goods_name')->where(['in', 'goods_id', explode(',', $post->goods_id)])->andWhere(['store_id' => $this->visitor['store_id'], 'closed' => 0, 'if_show' => 1])->asArray()->all())) {
			return Message::warning(Language::get('no_such_goods'));
        }

		if(!Yii::$app->request->isPost)
		{
			// 单个编辑
			$goods = current($list);
			$setting = DistributeSettingModel::find()->where(['item_id' => $goods['goods_id'], 'type' => 'goods'])->asArray()->one();
			
			$this->params['setting'] = $setting;
			$this->params['goodsList'] = $list;
			$this->params['action'] = Url::toRoute(['distribute/edit','goods_id' => $post->goods_id]);

			$this->params['page'] = Page::seo(['title' => Language::get('set_ratio')]);
			return $this->render('../distribute.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['enabled']);
			$post = $this->absratio($post);

			// 商家是否已购买，并在使用期限内
			if(!Promotool::getInstance('distribute')->build(['store_id' => Yii::$app->user->id])->checkAvailable(false)) {
				return Message::popWarning(Language::get('handle_exception'));
			}

			if(($post->ratio1 + $post->ratio2 + $post->ratio3 >= 1) || ($post->ratio1 + $post->ratio2 + $post->ratio3 <= 0)) {
				return Message::popWarning(Language::get('ratio_invalid'));
			}
			$counts = 0;
			foreach($list as $key => $val){
				if(!$model = DistributeSettingModel::find()->where(['item_id' => $val['goods_id'], 'type' => 'goods'])->one()) {
					$model = new DistributeSettingModel();
				}
				
				$model->enabled 	= $post->enabled;
				$model->ratio1 		= $post->ratio1;
				$model->ratio2 		= $post->ratio2;
				$model->ratio3 		= $post->ratio3;
				$model->type 		= 'goods';
				$model->item_id		= $val['goods_id'];

				if($model->save()) {
					$counts++;
				}
			}
			
			if($counts <= 0 ) {
				return Message::popWarning(Language::get('edit_fail'));
			}

			return Message::popSuccess('ok');
		}
	}
	
	public function actionDisable()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		$allId = explode(',', $post->goods_id);

		// 只允许删除自己的商品
		if(empty($allId) || GoodsModel::find()->where(['and', ['in', 'goods_id', $allId], ['!=', 'store_id', $this->visitor['store_id']]])->exists()) {
			return Message::warning(Language::get('disable_fail'));
		}

		if(!DistributeSettingModel::deleteAll(['and', ['type' => 'goods'], ['in', 'item_id', $allId]])) {
			return Message::warning(Language::get('disable_fail'));
		}
		return Message::display(Language::get('disable_ok'));	
	}

	/**
	 * 禁止负值或非数值处理
	 */
	private function absratio($post) {
		$post->ratio1 = abs(floatval($post->ratio1));
		$post->ratio2 = abs(floatval($post->ratio2));
		$post->ratio3 = abs(floatval($post->ratio3));

		return $post;
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'distribute_goods',
                'url'   => Url::toRoute('distribute/index'),
            )
        );
		
        return $submenus;
    }
}