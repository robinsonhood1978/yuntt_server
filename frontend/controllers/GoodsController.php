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
use yii\captcha\CaptchaValidator;

use common\models\UserModel;
use common\models\GoodsModel;
use common\models\GoodsQaModel;
use common\models\GoodsPropModel;
use common\models\GoodsPvsModel;
use common\models\GoodsPropValueModel;
use common\models\GoodsStatisticsModel;
use common\models\OrderGoodsModel;
use common\models\GcategoryModel;
use common\models\StoreModel;
use common\models\RegionModel;
use common\models\IntegralSettingModel;
use common\models\MealGoodsModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Promotool;

/**
 * @Id GoodsController.php 2018.6.6 $
 * @author mosir
 */

class GoodsController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('store');
		$this->params = ArrayHelper::merge($this->params, [
			'navs'	=> NavigationModel::getList()
		]);
	}
	
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		// 更新浏览量
		GoodsStatisticsModel::updateStatistics($post->id, 'views');
		
		// 搭配套餐
		// $this->params['goods']['meals'] = MealGoodsModel::getMealGoods($post->id);
		
		// 商品属性
		$this->params['goods']['props'] = $this->getGoodsProps($post->id);
		
		// 最近的商品评价
		$this->params['gcomments'] = array_merge($this->getComments($post->id, 10), GoodsStatisticsModel::getCommectStatistics($post->id));
		
		// 浏览历史
		$this->params['goods']['historys'] = GoodsModel::history($post->id, 5, true);
		
		$this->params['_head_tags'] = Resource::import('goodsinfo.js');
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/fresco/fresco.js,cart.js,jquery.plugins/raty/jquery.raty.js,jquery.plugins/jquery.lazyload.js',
            'style' =>  'jquery.plugins/fresco/fresco.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal($this->getCurlocal($this->params['goods']['cate_id']));
	
		$this->params['page'] = Page::seo([
			'title' => $this->params['goods']['goods_name'], 
			'keywords' => $this->params['goods']['brand'] . ',' . implode(',', (array)$this->params['goods']['tags']) . ',' . GcategoryModel::formatCateName($this->params['goods']['cate_name'], false),
			'description' => $this->params['goods']['goods_name']
		]);
        return $this->render('../goods.index.html', $this->params);
    }
	
	/* 商品评价 */
    public function actionComment()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		// 商品评价
		list($list, $page, $count) = array_values($this->getComments($post->id, 20));
		$this->params['gcomments'] = array_merge(['list' => $list, 'count' => $count], GoodsStatisticsModel::getCommectStatistics($post->id));
		$this->params['pagination'] = Page::formatPage($page);

		// 浏览历史
		$this->params['goods']['historys'] = GoodsModel::history($post->id, 5, true);

		$this->params['_head_tags'] = Resource::import('goodsinfo.js');
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/fresco/fresco.js,jquery.plugins/raty/jquery.raty.js,cart.js,jquery.plugins/jquery.lazyload.js',
            'style' =>  'jquery.plugins/fresco/fresco.css'
		]);

		// 当前位置
		$this->params['_curlocal'] = Page::setLocal($this->getCurlocal($this->params['goods']['cate_id']));
		
		$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
		return $this->render('../goods.comments.html', $this->params);
    }
	
	/* 销售记录 */
    public function actionSalelog()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page']);
		if($this->setGoodsCommonInfo($post->id) === false) {
			return Message::warning($this->errors);
		}
		
		// 商品销量
		list($list, $page, $count) = array_values($this->getSaleLogs($post->id, 20));
		$this->params['gsales'] = ['list' => $list, 'count' => $count];
		$this->params['pagination'] = Page::formatPage($page);

		// 浏览历史
		$this->params['goods']['historys'] = GoodsModel::history($post->id, 5, true);
	
		$this->params['_head_tags'] = Resource::import('goodsinfo.js');
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/fresco/fresco.js,jquery.plugins/raty/jquery.raty.js,cart.js,jquery.plugins/jquery.lazyload.js',
            'style' =>  'jquery.plugins/fresco/fresco.css'
		]);

		// 当前位置
		$this->params['_curlocal'] = Page::setLocal($this->getCurlocal($this->params['goods']['cate_id']));
		
		$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
		return $this->render('../goods.salelog.html', $this->params);
    }
	
	/* 商品咨询 */
	public function actionQa()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), ['id', 'page']);

		if(!$get->id || !($goods = GoodsModel::find()->select('goods_name,store_id')->where(['goods_id' => $get->id])->one())) {
			return Message::warning('no_such_goods');
		}

        if(!Yii::$app->request->isPost)
        {
			if($this->setGoodsCommonInfo($get->id) === false) {
				return Message::warning($this->errors);
			}
		
			// 商品咨询
			list($list, $page, $count) = array_values($this->getGoodsQas($get->id, 20));
			$this->params['gqas'] = ['list' => $list, 'count' => $count];
			$this->params['pagination'] = Page::formatPage($page);

			// 浏览历史
			$this->params['goods']['historys'] = GoodsModel::history($post->id, 5, true);
		
			$this->params['_head_tags'] = Resource::import('goodsinfo.js');
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.plugins/fresco/fresco.js,jquery.plugins/raty/jquery.raty.js,cart.js,jquery.plugins/jquery.lazyload.js,jquery.plugins/jquery.form.js,',
				'style' =>  'jquery.plugins/fresco/fresco.css'
			]);
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal($this->getCurlocal($this->params['goods']['cate_id']));

			$this->params['page'] = Page::seo(['title' => $this->params['goods']['goods_name']]);
			return $this->render('../goods.qa.html', $this->params);   
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			if(Yii::$app->user->isGuest) {
				return Message::warning(sprintf(Language::get('login_to_ask'), Url::toRoute('user/login')));
			}
			
			if(Yii::$app->user->id == $this->visitor['store_id']) {
				return Message::warning(Language::get('not_question_self'));
			}
			
			if(empty($post->content)) {
				return Message::warning(Language::get('content_not_null'));
			}
			if(!empty($post->email) && !Basewind::isEmail($post->email)) {
				return Message::warning(Language::get('email_not_correct'));
			}
			if(Yii::$app->params['captcha_status']['goodsqa']) {
				$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
				if(!$captchaValidator->validate($post->captcha)) {
					return Message::warning(Language::get('captcha_failed'));
				}
			}
			
			$model = new GoodsQaModel();
			$model->question_content = $post->content;
			$model->type = 'goods';
			$model->item_id = $get->id;
			$model->item_name = addslashes($goods->goods_name);
			$model->store_id = $goods->store_id;
			$model->email = $post->email ? $post->email : '';
			$model->userid = $post->hide_name ? 0 : Yii::$app->user->id;
			$model->time_post = Timezone::gmtime();
			if(!$model->save()) {
				return Message::warning(Language::get('post_qa_fail'));
			}
			return Message::display(Language::get('post_qa_ok'), ['goods/qa', 'id' => $get->id]);
        }
    }

	/* [异步JS请求]在此获取该商品的促销策略，包括促销商品，会员价格，手机专享优惠价格等 */
	public function actionPromoinfo()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['goods_id', 'spec_id']);
	
		$promotool = Promotool::getInstance()->build();
		if(($result = $promotool->getItemProInfo($post->goods_id, $post->spec_id)) === false) {
			return Message::warning($result);
		}
		return Message::result($result);
	}
	
	/* [异步JS获取地区数据]*/
	public function actionRegioninfo()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['parent_id']);
		$id = isset($post->parent_id) ? $post->parent_id : 0;
		
		$result = RegionModel::getList($id);
		return Message::result($result);
	}
	
	/* 获取商品页，评价页，销售页等公共数据 */
	private function setGoodsCommonInfo($id = 0)
	{
		$goods = GoodsModel::find()->alias('g')->select('g.*,gst.sales,gst.comments,gi.max_exchange')->joinWith('goodsStatistics gst', false)->joinWith('goodsIntegral gi', false)->with(['goodsImage'=>function($query){$query->orderBy('sort_order');}])->with('goodsSpec')->where(['g.goods_id' => $id])->asArray()->one();
		
		if(!$id || !$goods) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}
		if (($goods['if_show'] == 0) || ($goods['closed'] == 1)) {
			$this->errors = Language::get('goods_not_exist');
			return false;
    	}
	
		// 判断店铺是否开启
		$store = StoreModel::getStoreAssign($goods['store_id']);
		if (!$store || ($store['state'] == Def::STORE_CLOSED)) {
 			$this->errors = Language::get('the_store_is_closed');
			return false;
   		}

		$goods['tags'] = $goods['tags'] ? Basewind::trimAll(explode(',', str_replace('，', ',',$goods['tags']))) : array();

		// 读取商品促销价格
		$promotool = Promotool::getInstance()->build();
		$goods['promotion'] = $promotool->getItemProInfo($goods['goods_id'], $goods['default_spec']);
		
		// 商品积分
		if(IntegralSettingModel::getSysSetting('enabled')) {
			$goods['integral_enabled'] = true;
			$goods['exchange_price'] = IntegralSettingModel::getSysSetting('rate') * $goods['max_exchange'];
		}

		// 商品二维码
		//$goods['qrcode'] = Page::generateQRCode('goods', array('goods_id' => $id), true);
		
		// 获取商品详情页显示所有该商品具有的营销工具信息
		$promotool = Promotool::getInstance()->build(['store_id' => $goods['store_id']]);
   		$this->params['promotool'] = $promotool->getGoodsAllPromotoolInfo($id);
			
		$this->params['goods'] = $goods;
		
		// 页面公共参数
		$this->params = array_merge($this->params, Page::getAssign('store', $goods['store_id']));
		$this->params['store'] = array_merge($store, (array)$this->params['store']);
		$this->params['default_image'] = Yii::$app->params['default_goods_image'];
	}
	
	/* 取得商品评价 */
    private function getComments($goods_id = 0, $pageper = 10, $commented = false)
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['evaluation']);
	
		$query = OrderGoodsModel::find()->alias('og')->select('og.evaluation,og.comment,og.reply_comment,og.reply_time,o.buyer_id,o.buyer_name,o.evaluation_status,o.evaluation_time')->joinWith('order o', false)->where(['goods_id' => $goods_id, 'o.evaluation_status' => 1])->orderBy(['o.evaluation_time' => SORT_DESC]);
		if($commented) {
			$query->andWhere(['>', 'comment', '']);
		}
		
		// 数据库字段记录的是5分制，3分为中评
		if(isset($post->level)) {
			if($post->level == 1) $query->andWhere(['<', 'evaluation', 3]);
			if($post->level == 2) $query->andWhere(['=', 'evaluation', 3]);
			if($post->level == 3) $query->andWhere(['>', 'evaluation', 3]);
		}
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $val)
		{
			if(!($portrait = UserModel::find()->select('portrait')->where(['userid' => $val['buyer_id']])->scalar())) {
				$portrait = Yii::$app->params['default_user_portrait'];
			}
			$list[$key]['portrait'] = $portrait; 
		}
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());

		return $result;
    }
	
	/* 取得销售记录 */
    private function getSaleLogs($goods_id = 0, $pageper = 10)
    {
		$query = OrderGoodsModel::find()->alias('og')->select('buyer_id,buyer_name,add_time,anonymous,goods_id,specification, price, quantity, evaluation')->joinWith('order o', false)->where(['goods_id' => $goods_id, 'o.status' => Def::ORDER_FINISHED])->orderBy(['add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());
	
        return $result;
    }
	
	/* 取得商品咨询 */
    private function getGoodsQas($goods_id = 0, $pageper = 10, $replied = false)
    {
		$query = GoodsQaModel::find()->alias('ga')->select('u.username,question_content,reply_content,time_post,time_reply')->joinWith('user u', false)->where(['item_id' => $goods_id, 'type' => 'goods'])->orderBy(['time_post' => SORT_DESC]);
		if($replied) {
			$query->andWhere(['>', 'reply_content', '']);
		}
		
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$result = array('list' => $list, 'page' => $page, 'count' => $query->count());
		
        return $result;
    }
	
	/* 获取商品属性 */
	private function getGoodsProps($goods_id = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$result = array();
			if(($pvs = GoodsPvsModel::find()->select('pvs')->where(['goods_id' => $goods_id])->scalar())) 
			{
				$pvs = explode(';', $pvs);
				foreach($pvs as $pv)
				{
					if(!$pv) continue;
					$pv = explode(':', $pv);
					if(($prop = GoodsPropModel::find()->where(['pid' => $pv[0], 'status' => 1])->one())) {
						if(($value = GoodsPropValueModel::find()->where(['pid' => $prop->pid, 'vid' => $pv[1], 'status' => 1])->one())) {
							if(isset($result[$prop->pid]['value'])) $result[$prop->pid]['value'] .= '，' . $value['pvalue'];
							else $result[$prop->pid] = array('name' => $prop->name, 'value' => $value['pvalue']);
						}
					}
				}
			}
			$data = array_values($result);
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
	
	/* 取得当前位置 */
	private function getCurlocal($cate_id = 0)
    {
        $parents = array();
        if ($cate_id) {
            $parents = GcategoryModel::getAncestor($cate_id);
        }

        $curlocal = array(['text' => Language::get('all_categories'), 'url' => Url::toRoute(['category/index'])]);
        foreach ($parents as $category) {
            $curlocal[] = ['text' => $category['cate_name'], 'url' => Url::toRoute(['search/index', 'cate_id' => $category['cate_id']])];
        }
        $curlocal[] = array('text' => Language::get('goods_detail'));

        return $curlocal;
    }
}
