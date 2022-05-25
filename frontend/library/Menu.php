<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\library;

use yii;
use yii\helpers\Url;

use common\models\StoreModel;
use common\models\IntegralSettingModel;
use common\models\DistributeMerchantModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id Menu.php 2018.4.1 $
 * @author mosir
 */
 
class Menu
{
	/* 用户中心页面栏目：当前选中的菜单项 */
    public static function curitem($item = null)
    {
		$userRole = array();
		$userMenu = self::getUserMenus();
	
		// 包含关闭状态，是为了查看店铺历史数据
		if(!(StoreModel::find()->where(['and', ['store_id' => Yii::$app->user->id], ['in', 'state', [Def::STORE_OPEN,Def::STORE_CLOSED]]])->exists())) 
		{
			unset($userMenu['im_seller'], $userMenu['promotool']);
			$userRole = 'buyer';
		}
		else
		{
			if(Yii::$app->session->get('userRole') == 'buyer') {
				unset( $userMenu['im_seller'], $userMenu['promotool']);
				$userRole = 'buyer';
			}
			else {
				unset($userMenu['my_account'], $userMenu['im_buyer']);
				$userRole = 'seller';
			}
		}
		Yii::$app->session->set('userRole', $userRole);
		return array('menus' => $userMenu, 'curitem' => $item);
    }
	
    /* 用户中心页面栏目：当前选中的子菜单 */
    public static function curmenu($item = null)
    {
        $userSubmenu = Yii::$app->controller->getUserSubmenu();
        foreach ($userSubmenu as $key => $value) {
            $userSubmenu[$key]['text'] = (isset($value['text']) && $value['text']) ? $value['text'] : Language::get($value['name']);
        }
		return array('submenus' => $userSubmenu, 'curmenu' => $item);
    }
	
	/* 获取用户中心全局菜单列表 */
    public static function getUserMenus()
    {
        $menu = array();
	
		// 我的账户
		$menu['my_account'] = array(
			'name'  => 'my_account',
			'text'  => Language::get('my_account'),
			'submenu'   => array(
				'overview'  => array(
					'text'  => Language::get('overview'),
					'url'   => Url::toRoute(['user/index']),
					'name'  => 'overview',
				),
				'my_profile'  => array(
					'text'  => Language::get('my_profile'),
					'url'   => Url::toRoute(['user/profile']),
					'name'  => 'my_profile',
				),
				'connect'  => array(
					'text'  => Language::get('connect_bind'),
					'url'   => Url::toRoute(['connect/index']),
					'name'  => 'connect',
				),
				'my_message'  => array(
					'text'  => Language::get('message'),
					'url'   => Url::toRoute(['my_message/index']),
					'name'  => 'my_message',
				),
				// 'friend'  => array(
				// 	'text'  => Language::get('friend'),
				// 	'url'   => Url::toRoute(['friend/index']),
				// 	'name'  => 'friend',
				// ),
				'deposit' => array(
					'text'	=> Language::get('deposit'),
					'url'	=> Url::toRoute(['deposit/index']),
					'name'  => 'deposit',
				),
				'my_coupon'  => array(
					'text'  => Language::get('my_coupon'),
					'url'   => Url::toRoute(['my_coupon/index']),
					'name'  => 'my_coupon',
				),
				'my_integral'  => array(
					'text'  => Language::get('my_integral'),
					'url'   => Url::toRoute(['my_integral/index']),
					'name'  => 'my_integral',
				),
				'my_report' => array(
					'text'	=> Language::get('my_report'),
					'url'	=> Url::toRoute(['my_report/index']),
					'name'  => 'my_report',
				)
			)
		);
	
		// 我是买家
		$menu['im_buyer'] = array(
			'name'  => 'im_buyer',
			'text'  => Language::get('my_order'),
			'submenu'   => array(
				'my_order'  => array(
					'text'  => Language::get('my_order'),
					'url'   => Url::toRoute(['buyer_order/index']),
					'name'  => 'my_order',
				),
				// 我申请的退款
				'refund' => array(
					'text' => Language::get('refund_apply'),
					'url'  => Url::toRoute(['refund/index']),
					'name' => 'refund_apply',
				),
				'my_address'  => array(
					'text'  => Language::get('my_address'),
					'url'   => Url::toRoute(['my_address/index']),
					'name'  => 'my_address',
				),
				'my_favorite'  => array(
					'text'  => Language::get('my_favorite'),
					'url'   => Url::toRoute(['my_favorite/index']),
					'name'  => 'my_favorite',
				),
				'my_question' =>array(
					'text'  => Language::get('my_question'),
					'url'   => Url::toRoute(['my_question/index']),
					'name'  => 'my_question',
				)
			)
		);
			
		// 我是卖家（包含关闭状态，是为了查看店铺历史数据）
		if(StoreModel::find()->where(['and', ['store_id' => Yii::$app->user->id], ['in', 'state', [Def::STORE_OPEN,Def::STORE_CLOSED]]])->exists())
		{
			if(($smser = Plugin::getInstance('sms')->autoBuild())) {
				$menu['my_account']['submenu']['msg'] = array(
					'text'  => Language::get('msg'),
					'url'   => Url::toRoute(['msg/index']),
					'name'  => 'msg',
				);
			}
			// 指定了要管理的店铺
			$menu['im_seller'] = array(
				'name'  => 'im_seller',
				'text'  => Language::get('basic_funs'),
				'submenu'   => array(),
			);
	
			$menu['im_seller']['submenu']['my_goods'] = array(
				'text'  => Language::get('my_goods'),
				'url'   => Url::toRoute(['my_goods/index']),
				'name'  => 'my_goods',
			);
			
			$menu['im_seller']['submenu']['seller_order'] = array(
				'text'  => Language::get('seller_order'),
				'url'   => Url::toRoute(['seller_order/index']),
				'name'  => 'seller_order',
			);
			// 退款管理
			$menu['im_seller']['submenu']['refund_receive']  = array(
				'text' => Language::get('refund_receive'),
				'url'  => Url::toRoute(['refund/receive']),
				'name' => 'refund_receive',
			);
			$menu['im_seller']['submenu']['my_comment'] = array(
				'text'  => Language::get('my_comment'),
				'url'   => Url::toRoute(['my_comment/index']),
				'name'  => 'my_comment',
			);
			$menu['im_seller']['submenu']['my_store']  = array(
				'text'  => Language::get('my_store'),
				'url'   => Url::toRoute(['my_store/index']),
				'name'  => 'my_store',
			);
			$menu['im_seller']['submenu']['my_payment'] =  array(
				'text'  => Language::get('my_payment'),
				'url'   => Url::toRoute(['my_payment/index']),
				'name'  => 'my_payment',
			);
				
			$menu['im_seller']['submenu']['my_delivery'] = array(
				'text'  => Language::get('my_delivery'),
				'url'   => Url::toRoute(['my_delivery/index']),
				'name'  => 'my_delivery',
			);
				
			// $menu['im_seller']['submenu']['my_navigation'] = array(
			// 	'text'  => Language::get('my_navigation'),
			// 	'url'   => Url::toRoute(['my_navigation/index']),
			// 	'name'  => 'my_navigation',
			// );
			  
			$menu['im_seller']['submenu']['seller_coupon']  = array(
				'text'  => Language::get('seller_coupon'),
				'url'   => Url::toRoute(['seller_coupon/index']),
				'name'  => 'seller_coupon',
			);

			// 应用市场
			$menu['im_seller']['submenu']['appmarket']  = array(
				'text' => Language::get('appmarket'),
				'url'  => Url::toRoute(['appmarket/index']),
				'name' => 'appmarket',
			);
			$menu['im_seller']['submenu']['my_category'] = array(
				'text'  => Language::get('my_category'),
				'url'   => Url::toRoute(['my_category/index']),
				'name'  => 'my_category',
			);
			$menu['im_seller']['submenu']['my_qa'] = array(
				'text'  => Language::get('my_qa'),
				'url'   => Url::toRoute(['my_qa/index']),
				'name'  => 'my_qa',
			);
				
			// 营销中心
			$menu['promotool'] = array(
				'name'  => 'promotool',
				'text'  => Language::get('promotool'),
				'submenu'   => array(),
			);
			$menu['promotool']['submenu']['teambuy'] = array(
				'text'  => Language::get('teambuy'),
				'url'   => Url::toRoute(['teambuy/index']),
				'name'  => 'teambuy',
			);
			$menu['promotool']['submenu']['seller_limitbuy'] = array(
				'text'  => Language::get('seller_limitbuy'),
				'url'   => Url::toRoute(['seller_limitbuy/index']),
				'name'  => 'seller_limitbuy',
			);
			$menu['promotool']['submenu']['seller_meal'] = array(
				'text'  => Language::get('seller_meal'),
				'url'   => Url::toRoute(['seller_meal/index']),
				'name'  => 'seller_meal',
			);
			$menu['promotool']['submenu']['seller_fullfree'] = array(
				'text'  => Language::get('seller_fullfree'),
				'url'   => Url::toRoute(['seller_fullfree/index']),
				'name'  => 'seller_fullfree',
			);
			$menu['promotool']['submenu']['seller_fullprefer'] = array(
				'text'  => Language::get('seller_fullprefer'),
				'url'   => Url::toRoute(['seller_fullprefer/index']),
				'name'  => 'seller_fullprefer',
			);
			$menu['promotool']['submenu']['wholesale'] = array(
				'text' => Language::get('wholesale'),
				'url' => Url::toRoute(['wholesale/index']),
				'name' => 'wholesale',
			);
			$menu['promotool']['submenu']['distribute'] = array(
				'text' => Language::get('distribute'),
				'url' => Url::toRoute(['distribute/index']),
				'name' => 'distribute',
			);
			$menu['promotool']['submenu']['seller_exclusive'] = array(
				'text'  => Language::get('seller_exclusive'),
				'url'   => Url::toRoute(['seller_exclusive/index']),
				'name'  => 'seller_exclusive',
			);
		}
		elseif(Yii::$app->params['store_allow'])
		{
			$menu['overview'] = array(
				'text' => Language::get('apply_store'),
				'url'  => Url::toRoute(['apply/index']),
				'name'  => 'apply_store'
			);
		}

        return $menu;
    }
}