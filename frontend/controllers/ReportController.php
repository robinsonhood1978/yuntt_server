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
use yii\captcha\CaptchaValidator;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\ReportModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id ReportController.php 2018.4.17 $
 * @author MH
 */

class ReportController extends \common\controllers\BaseUserController
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
		$id = intval(Yii::$app->request->get('id'));
		
		if(!$id || !($goods = GoodsModel::find()->alias('g')->select('g.default_image, g.goods_id, g.goods_name, g.cate_name, g.store_id, s.store_name')->joinWith('store s', false)->where(['goods_id' => $id])->asArray()->one())){
			return Message::warning(Language::get('no_such_goods'));
		}

		if (!Yii::$app->request->isPost)
        {
			$goods['cate_name'] = GcategoryModel::formatCateName($goods['cate_name'], false, '/');
			$this->params['goods'] = $goods;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			$this->params['page'] = Page::seo(['title' => Language::get('add_report')]);
        	return $this->render('../report.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, [], ['content', 'captcha']);
			
			if(empty($post->content)){
				return Message::warning(Language::get('content_no_empty'));
			}
			
			$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
			if(!$captchaValidator->validate($post->captcha)) {
				return Message::warning(Language::get('captcha_failed'));
			}
			
			$model = new ReportModel();
			$model->userid  = Yii::$app->user->id;
			$model->goods_id = $goods['goods_id'];
			$model->store_id = $goods['store_id'];
			$model->content  = $post->content;
			$model->status = 0;
			$model->add_time = Timezone::gmtime();
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_report_ok'), ['my_report/index']);
		}
	}
}