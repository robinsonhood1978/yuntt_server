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

use common\models\CodModel;
use common\models\PluginModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_paymentController.php 2018.5.30 $
 * @author mosir
 */

class My_paymentController extends \common\controllers\BaseSellerController
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
		$payments = PluginModel::find()->select('code,name,desc,enabled')->where(['instance' => 'payment'])->indexBy('code')->asArray()->all();
		foreach($payments as $key => $value) {
			if($key == 'cod') {
				if(($query = CodModel::find()->where(['store_id' => $this->visitor['store_id']])->one())) {
					$payments[$key]['regions'] = unserialize($query->regions);
					$payments[$key]['status'] = $query->status;
				}
				break;
			}
		}
		$this->params['payments'] = $payments;
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js,mlselection.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_payment'), Url::toRoute('my_payment/index'), Language::get('payment_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_payment', 'payment_list');

		$this->params['page'] = Page::seo(['title' => Language::get('payment_list')]);
        return $this->render('../my_payment.index.html', $this->params);
	}	
	
	/**
	 * 只允许卖家配置货到货款的付款方式
	 */
	public function actionConfig()
	{
		$code = Yii::$app->request->get('code');

		// 只允许卖家配置货到货款的付款方式
		if(strtolower($code) != 'cod') {
			return Message::warning(Language::get('not_allow_config'));
		}

		if(!($payment = PluginModel::find()->where(['instance' => 'payment', 'code' => $code, 'enabled' => 1])->one())) {
			return Message::warning(Language::get('no_such_payment'));
		}

		if(!Yii::$app->request->isPost)
		{
			$payment = ArrayHelper::toArray($payment);
			$query = CodModel::find()->select('status,regions')->where(['store_id' => $this->visitor['store_id']])->one();
			if($query) {
				$payment['regions'] = $query->regions ? unserialize($query->regions) : array();
				$payment['status'] = $query->status;
			}
			$this->params['payment'] = $payment;
			$this->params['allregions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->indexBy('region_id')->column();
			$this->params['yes_or_no'] = array(Language::get('no'), Language::get('yes'));
			$this->params['action'] = Url::toRoute(['my_payment/config', 'code' => $code]);
			
			return $this->render('../my_payment.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status']);
			$payment->desc = $post->desc;
			$payment->save();

			if(!($model = CodModel::find()->where(['store_id' => $this->visitor['store_id']])->one())) {
				$model = new CodModel();
				$model->store_id = $this->visitor['store_id'];
			}
			$model->regions = $post->regions ? serialize(ArrayHelper::toArray($post->regions)) : '';
			$model->status = $post->status;

			if(!$model->save()) {
				return Message::popWarning($model->errors);
			}
            return Message::popSuccess();
		}
	}
	
	/**
	 * 只允许卖家安装货到货款的付款方式
	 */
	public function actionInstall()
	{
		$code = Yii::$app->request->get('code');
		
		if(strtolower($code) != 'cod') {
			return Message::warning(Language::get('not_allow_install'));
		}

		if(!($payment = PluginModel::find()->where(['instance' => 'payment', 'code' => $code])->one())) {
			return Message::warning(Language::get('no_such_payment'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['payment'] = ArrayHelper::toArray($payment);
			$this->params['allregions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->indexBy('region_id')->column();
			$this->params['yes_or_no'] = array(Language::get('no'), Language::get('yes'));
			$this->params['action'] = Url::toRoute(['my_payment/install', 'code' => $code]);
			
			return $this->render('../my_payment.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['status']);
			
			if(!($model = CodModel::find()->where(['store_id' => $this->visitor['store_id']])->one())) {
				$model = new CodModel();
				$model->store_id = $this->visitor['store_id'];
			}
			$model->regions = $post->regions ? serialize(ArrayHelper::toArray($post->regions)) : '';
			$model->status = $post->status;

			if(!$model->save()) {
				return Message::popWarning($model->errors);
			}
            return Message::popSuccess();
		}
	}
	
	/**
	 * 目前仅允许卸载商家的货到付款
	 */
	public function actionUninstall()
	{
		$code = Yii::$app->request->get('code');

		if(strtolower($code) != 'cod') {
			return Message::warning(Language::get('no_such_payment'));
		}
		
		CodModel::deleteAll(['store_id' => $this->visitor['store_id']]);
		return Message::display(Language::get('uninstall_successed'));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'payment_list',
                'url'  => Url::toRoute(['my_payment/index']),
            )
        );

        return $submenus;
    }
}