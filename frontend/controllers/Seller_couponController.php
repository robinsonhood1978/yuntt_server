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

use common\models\UserModel;
use common\models\CouponModel;
use common\models\CouponsnModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id Seller_couponController.php 2018.5.20 $
 * @author mosir
 */

class Seller_couponController extends \common\controllers\BaseSellerController
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

		$query = CouponModel::find()->where(['store_id' => $this->visitor['store_id']])->orderBy(['coupon_id' => SORT_DESC]);	
		$page = Page::getPage($query->count(), 15);
		$this->params['coupons'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['pagination'] = Page::formatPage($page);
		$this->params['now'] = Timezone::gmtime();
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('seller_coupon'), Url::toRoute('seller_coupon/index'), Language::get('coupon_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('seller_coupon', 'coupon_list');

		$this->params['page'] = Page::seo(['title' => Language::get('coupon_list')]);
        return $this->render('../seller_coupon.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['action'] = Url::toRoute(['seller_coupon/add']);
			$this->params['now'] = Timezone::gmtime();
			
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_add')]);
        	return $this->render('../seller_coupon.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['available', 'clickreceive']);
			$model = new \frontend\models\Seller_couponForm(['store_id' => $this->visitor['store_id']]);
			
   			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			
			return Message::popSuccess();
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($coupon = CouponModel::find()->where(['store_id' => $this->visitor['store_id'], 'coupon_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_coupon'));
		}

		
		if(!Yii::$app->request->isPost)
		{
			$this->params['coupon'] = $coupon;
			$this->params['action'] = Url::toRoute(['seller_coupon/edit', 'id' => $id]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('coupon_edit')]);
        	return $this->render('../seller_coupon.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['clickreceive']);
			$model = new \frontend\models\Seller_couponForm(['coupon_id' => $id, 'store_id' => $this->visitor['store_id']]);
			
   			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			
			return Message::popSuccess();
		}
	}
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(!$post->id) {
			return Message::warning(Language::get('no_coupon'));
		}
		
		$coupons = CouponModel::find()->select('coupon_id')
			->where(['store_id' => $this->visitor['store_id']])
			->andWhere(['in', 'coupon_id', explode(',', $post->id)])
			->andWhere('available = 0 OR (available = 1 AND end_time < :end_time)', [':end_time' => Timezone::gmtime()])
			->column();
		
		if(empty($coupons) || !is_array($coupons)) {
			return Message::warning(Language::get('drop_disabled'));
		}
		
		if(!CouponModel::deleteAll(['in', 'coupon_id', $coupons])) {
			return Message::warning(Language::get('drop_fail'));
		}
        return Message::display(Language::get('drop_ok'));
    }
	
	/* 优惠券发放 */
	public function actionExtend()
    {
        $id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($coupon = CouponModel::find()->alias('coupon')->select('coupon.*,s.store_name')->joinWith('store s', false)->where(['s.store_id' => $this->visitor['store_id'], 'coupon_id' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_coupon'));
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['id'] = $id;
			return $this->render('../seller_coupon.extend.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(!$post->username) {
				return Message::popWarning(Language::get('involid_data'));	
			}
			
			if(($allName = explode("\n", str_replace(array("\r","\r\n"), "\n", $post->username))) && is_array($allName)) {
				$users = UserModel::find()->select('userid,username')->where(['in', 'username', $allName])->all();
			} 
	
			if(empty($users)) {
				return Message::popWarning(Language::get('involid_data'));
			}
            if (count($users) > 30) {
				return Message::popWarning(Language::get('amount_gt'));
            }
			
			// 是否还有足够的优惠券来发放
			if(CouponModel::find()->select('surplus')->where(['coupon_id' => $id])->scalar() < count($users)) {
				return Message::warning(Language::get('extend_no_enough'));
			}
			
			$sends = array();
			foreach($users as $user)
			{
				// 还有未使用的优惠卷不能再发放（暂时不明白为什么要做此限制，先屏蔽）
				/*if(CouponsnModel::find()->alias('cs')->select('cs.coupon_sn')->where(['userid' => $user->userid])->andWhere(['and', ['>','cs.remain_times', '0'], ['>', 'c.end_time', Timezone::gmtime()]])->joinWith('coupon c', false, 'INNER JOIN')->exists()){
					continue;
				}*/
				
				$model = new CouponsnModel();
				$model->coupon_sn = $model->createRandom();
				$model->coupon_id = $id;
				$model->remain_times = 1;
				$model->userid = $user->userid;
				if($model->save()) {
					if(!CouponModel::updateAllCounters(['surplus' => -1], ['coupon_id' => $id])) {
						$model->delete();
						continue;
					}
					$sends[] = array_merge(['coupon_sn' => $model->coupon_sn], ArrayHelper::toArray($user));
				}
			}
			
			foreach($sends as $send)
			{
				$pmer = Basewind::getPmer('touser_send_coupon', ['coupon' => $coupon, 'user' => $send]);
				if($pmer) {
					$pmer->sendFrom(0)->sendTo($send['userid'])->send();
				}
				
				// 发送给买家优惠券通知
				$mailer = Basewind::getMailer('touser_send_coupon', ['coupon' => $coupon, 'user' => $send]);
				if($mailer && ($toEmail = UserModel::find()->select('email')->where(['userid' => $send['userid']])->scalar())) {
					$mailer->setTo($toEmail)->send();
				}
				
			}
			return Message::popSuccess();    
        }
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'coupon_list',
                'url'  => Url::toRoute(['seller_coupon/index']),
            )
        );

        return $submenus;
    }
}