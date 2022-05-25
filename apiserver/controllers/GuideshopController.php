<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\GuideshopModel;
use common\models\RegionModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Weixin;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id GuideshopController.php 2020.2.15 $
 * @author yxyc
 */

class GuideshopController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取团长门店列表
	 * @api 接口访问地址: http://api.xxx.com/guideshop/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		
		$query = GuideshopModel::find()->select('*')->where(['status' => Def::STORE_OPEN]);
		$query = $this->getNearbyConditions($post, $query);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key] = array_merge($value, RegionModel::getArrayRegion($value['region_id'], $value['region_name']));
			unset($list[$key]['region_name']);

			$list[$key]['banner'] = Formatter::path($value['banner']);
			$list[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $value['created']);
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
    }
	
	/**
	 * 获取团长门店单条信息
	 * @api 接口访问地址: http://api.xxx.com/guideshop/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'status', 'ownerid']);
		
		$query = GuideshopModel::find();
		if($post->id) {
			$query->where(['id' => $post->id]);
		} elseif($post->ownerid) {
			$query->where(['userid' => $post->ownerid]);
		} else {
			$query->where(['userid' => Yii::$app->user->id]);
		}

		if(isset($post->status) && in_array($post->status, [Def::STORE_APPLYING,Def::STORE_OPEN,Def::STORE_CLOSED,Def::STORE_NOPASS])) {
			$query->andWhere(['status' => $post->status]);
		}
		if(($record = $query->asArray()->one())) {
			$record['banner'] = Formatter::path($record['banner']);
			$record['created'] = Timezone::localDate('Y-m-d H:i:s', $record['created']);

			$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
			unset($this->params['region_name']);
		}

		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 插入团长门店信息
	 * @api 接口访问地址: http://api.xxx.com/guideshop/add
	 */
    public function actionAdd()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);
		
		$model = new \apiserver\models\GuideshopForm();
		if(!($record = $model->save($post))) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors ? $model->errors : Language::get('add_fail'));
		}
		foreach($record as $key => $value) {
			if(!in_array($key, ['remark', 'status', 'id'])) {
				unset($record[$key]);
			}
		}

		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);

		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 更新团长门店信息
	 * @api 接口访问地址: http://api.xxx.com/guideshop/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);
		
		$model = new \apiserver\models\GuideshopForm();
		if(!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if(!($record = $model->save($post))) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors ? $model->errors : Language::get('update_fail'));
		}
		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 删除团长门店数据
	 * @api 接口访问地址: http://api.xxx.com/guideshop/delete
	 */
    public function actionDelete()
    {
		
	}

	/**
	 * 获取社区团购品目ID
	 * @api 接口访问地址: http://api.xxx.com/guideshop/category
	 */
    public function actionCategory()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		$result = GuideshopModel::getCategoryId(true);
		$this->params['list'] = $result ? $result : null;
		
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取团长门店推广海报
	 * @api 接口访问地址: http://api.xxx.com/guideshop/qrcode
	 */
	public function actionQrcode()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		if(!$post->id || !($model = GuideshopModel::find()->select('name')->where(['id' => $post->id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}

		$path = UploadedFileModel::getSavePath(0, Def::BELONG_POSTER, $post->id);
		$wxacode = $path . "/wxacode".md5($post->page).".png";
		if(!file_exists($wxacode)) {
			$response = Weixin::getInstance(null, 0, 'applet')->getWxaCode(['path' => $post->page, 'width' => 280], $wxacode);
			if($response === false) {
				return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
			}
		}
	
		// 生成海报
		$config = [
			'text' => [['text' => '提货点：'. $model->name, 'top' => 1500]],
			'image' => [['url' => $wxacode, 'left' => 100, 'top' => 1600, 'width' => 280, 'height' => 280]],
			'background' => Yii::getAlias('@frontend'). '/web/static/images/guideshopQrcode.jpg'
		];

		$qrcode = Page::createPoster($config, $path . "/poster".md5($post->page).".png");
		$result['poster'] = str_replace(Yii::getAlias('@frontend'). '/web', Basewind::homeUrl(), $qrcode);

		return $respond->output(true, null, $result);
	}

	/**
	 * 根据坐标计算店铺距离，然后根据距离倒序显示数据
	 * @param int $distance  要检索的距离（米）
	 */
	private function getNearbyConditions($post, $query, $distance = 2000)
	{
		$latitude = $post->latitude;
		$longitude = $post->longitude;

		if(!$latitude || $latitude < 0 || !$longitude || $longitude < 0) {
			return $query;
		}

		// 距离半径判断（效率慢）
		//$fields = "sqrt( ( ((".$longitude."-longitude)*PI()*12656*cos(((".$latitude."+latitude)/2)*PI()/180)/180) * ((".$longitude."-longitude)*PI()*12656*cos (((".$latitude."+latitude)/2)*PI()/180)/180) ) + ( ((".$latitude."-latitude)*PI()*12656/180) * ((".$latitude."-latitude)*PI()*12656/180) ) )/2 as distance";
		
		// 这个效率待验证
		$fields = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))*1000 as distance";
		$query->addSelect($fields)->orderBy(['distance' => SORT_ASC]);

		// 如果不限定距离的，下面这行可以不需要
		//$query->having(['<', 'distance', $distance]);
		
		return $query;
	}
}