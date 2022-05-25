<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\library\Basewind;
use common\library\Language;
use common\library\Tree;

/**
 * @Id RegionModel.php 2018.4.22 $
 * @author mosir
 */

class RegionModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%region}}';
    }
	
	/**
     * 取得地区列表
     * @param int $parent_id 大于等于0表示取某个地区的下级地区，小于0表示取所有地区
	 * @param bool $shown    只取显示的地区
     * @return array
     */
    public static function getList($parent_id = -1, $shown = true, $cached = true)
    {
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$query = parent::find()->orderBy(['sort_order' => SORT_ASC, 'region_id' => SORT_ASC]);
			if($shown) $query->andWhere(['if_show' => 1]);
			
			if ($parent_id >= 0) {
				$query->where(['parent_id' => $parent_id]);
			
				// 处理第一级有多条记录的情况，比如：中国，日本，只取第一个顶级分类的地区（如果第一级是国家，请把注释去掉）
				//($parent_id == 0) && $query->limit(1);
			}
			$data = $query->asArray()->all();
			
			$cache->set($cachekey, $data, 3600);
		}
        return $data;
    }
	
	/**
	 * 取得所有地区 
	 * 保留级别缩进效果，一般用于select
	 * @return array(21 => 'abc', 22 => '&nbsp;&nbsp;');
	 */
    public static function getOptions($parent_id = -1, $except = null, $layer = 0, $shown = true, $space = '&nbsp;&nbsp;')
    {
		$regions = self::getList($parent_id, $shown);
		
		$tree = new Tree();
		$tree->setTree($regions, 'region_id', 'parent_id', 'region_name');
			
        return $tree->getOptions($layer, 0, $except, $space);
    }
	
	/* 寻找某ID的所有父级 */
	public static function getParents($id = 0, $selfIn = true)
	{
		$result = array();
		if(!$id) return $result;
		
		if($selfIn) $result[] = $id;
		while(($query = parent::find()->select('region_id,parent_id,region_name')->where(['region_id' => $id])->one())) {
			if($query->parent_id) $result[] = $query->parent_id;
			$id = $query->parent_id;
		}
		return array_reverse($result);
	}
	
	/**
     * 取得某分类的子孙分类id
     * @param int  $id     分类id
     * @param bool $cached 是否缓存
	 * @param bool $shown  只取要显示的地区
	 * @param bool $selfin 是否包含自身id
	 * @return array(1,2,3,4...)
	 */
	public static function getDescendantIds($id = 0, $cached = true, $shown = false, $selfin = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$conditions = $shown ? ['if_show' => 1] : null;
		
			$tree = new Tree();
			$data = $tree->recursive(new RegionModel(), $conditions)->getArrayList($id, 'region_id', 'parent_id', 'region_name')->fields($selfin);
						
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
	
	/**
	 * 获取省市数据
	 */
	public static function getProvinceCity()
	{
		$provinceParentId = self::getProvinceParentId();
		$provinces = self::getList($provinceParentId);
		
		foreach($provinces as $key => $province) {
			$provinces[$key]['cities'] = self::getList($province['region_id']);
		}
		return $provinces;
	}
	
	/**
	 * 获取省市区地址数组
	 * 如果通过$address的方式解析地址效率和成功率不行，可以考虑通过百度API解析接口
	 * 参考：http://lbsyun.baidu.com/index.php?title=webapi/address_analyze
	 * @used：api.map.baidu.com/address_analyzer/v1?address=北京市海淀区信息路甲九号&ak=你的ak
	 */
	public static function getArrayRegion($region_id = 0, $address = '')
	{
		if(!$address && $region_id) {
			$address = self::getRegionName(intval($region_id), true);
		}
		
		$array = explode(' ', self::replaceAddress($address));
		if(!$array) {
			return array();
		}

		// 处理直辖市的情况
		$directly = ['北京市', '上海市', '天津市', '重庆市'];
		foreach($directly as $key => $value) {
			$array[0] = str_replace(substr($value, 0, -3) . $value, $value, $array[0]); // example: 北京北京市=》北京市 
			if($array[0] == $value) {
				array_unshift($array, substr($value, 0, -3));// 把“市”去掉，中文是3个字符
				break;
			}
		}

		$result = array();
		$fields = ['province', 'city', 'district'];
		foreach($array as $key => $value) {
			$result[$fields[$key]] = $value;
			if($fields[$key] == 'district') {
				break;
			}
		}

		return $result;
	}

	/**
	 * 获取省市区地址末级ID
	 * @param array|string $address
	 */
	public static function getLastIdByName($address)
	{
		if(is_string($address)) {
			$address = self::getArrayRegion(0, self::replaceAddress($address));
		}

		// 如果地址不到区，不做处理
		if(isset($address['district']))
		{
			$district = str_replace(['区', '市'],['',''], $address['district']);
			$query = parent::find()->select('region_id,parent_id')->where(['in', 'region_name', [$address['district'], $district]]);
			if(!$query->exists()) {
				return 0;
			} 
			
			if($query->all()->count == 1) {
				return $query->one()->region_id;
			}
			
			$city = str_replace(['市'],[''], $address['city']);
			foreach($query->all() as $key => $value) {
				$parent = RegionModel::find()->select('region_id')->where(['in','region_name', [$address['city'], $city]])->andWhere(['region_id' => $value->parent_id])->one();
				if($parent) {
					return $value->region_id;
				}
			}
		}

		return 0;
	}

	/**
	 * 获取地址分组数据
	 */
	private static function replaceAddress($address = '')
	{
		$address = preg_replace("/\s/"," ", trim($address));
		$address = str_replace('中国', '', $address);
		$address = str_replace([' ', '省', '自治区', '市', '区','  '], ['', '省 ', '自治区 ',  '市 ', '区 ', ' '], $address);
		return $address;
	}
	
	/** 
	 * 获取省ID的上级ID，考虑第一级是中国的情况 
	 */
	public static function getProvinceParentId($topIsCountry = false)
	{
		if(!$topIsCountry) return 0;
		return parent::find()->select('region_id')->where(['parent_id' => 0])->limit(1)->orderBy(['region_id' => SORT_ASC])->scalar();
	}
	
	/**
	 * 获取多个/一个地区名称路径，主要用于运费模板功能
	 * @param int|string $region_ids 1 或 "2|3|4"
	 */
	public static function getRegionName($region_ids = 0, $full = false, $split = ',')
	{
		if(in_array($region_ids, [0])) {
			return '全国';
		}
		$str = '';
		foreach(explode('|', $region_ids) as $id)
		{
			$query = parent::find()->select('region_id,region_name,parent_id')->where(['region_id' => $id])->one();
			if($query) 
			{
				$str1 = $query->region_name;
				while($full && $query->parent_id != 0) {
					$query = parent::find()->select('region_id,region_name,parent_id')->where(['region_id' => $query->parent_id])->one();
					$str1 = $query->region_name .' '. $str1;
				}
				$str .= $split . $str1;
			}
		}
		return $str ? substr($str, 1) : $str;
	}
	
	/**
	 * 通过IP自动获取本地城市id
	 */
	public static function getCityIdByIp($cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$ip = Yii::$app->request->userIP;
			$address = self::getAddressByIp($ip);
			if($address && !$address['local'])
			{
				$province 	= $address['province'];
				$city 		= $address['city'];
				
				$provinceParentId = self::getProvinceParentId();
				$regionProvince = parent::find()->select('region_id,region_name')->where(['parent_id' => $provinceParentId])->andWhere(['in', 'region_name', [$province, str_replace('省', '', $province)]])->one();
			
				if($regionProvince) {
					$regionCity = parent::find()->select('region_id,region_name')->where(['parent_id' => $regionProvince->region_id])->andWhere(['in', 'region_name', [$city, str_replace('市', '', $city)]])->one();
					if($regionCity) {
						$data = $regionCity->region_id;
						$cache->set($cachekey, $data, 3600);
					}
				}
			}
		}
		return $data ? $data : 0;
	}
	
	/**
	 * 使用淘宝的IP库
	 * @api https://ip.taobao.com
	 */
	public static function getAddressByIp($ip = '')
	{
		if(empty($ip) || in_array($ip, ['127.0.0.1', 'localhost'])) {
			return ['city' => Language::get('local')];
		}

		$result = Basewind::curl('https://ip.taobao.com/outGetIpInfo.php?ip='.$ip);
		$result = json_decode($result);
		if($result->code == 0 && $result->data->city) {
			return array_merge(
				['province' => $result->data->region, 'city' => $result->data->city], 
				['local' => $result->data->city_id == 'local' ? true : false]
			);
		}
		return array();
	}
	
	/**
	 * 使用百度API
	 * 通过经纬度获取省市区数据
	 */
	public static function getAddressByCoord($latitude, $longitude)
	{
		$ak = Yii::$app->params['baidukey']['browser'];
		$gateway = 'https://api.map.baidu.com/geocoder';
		if($ak) {
			$gateway = 'https://api.map.baidu.com/reverse_geocoding/v3/';
		}
		$result = Basewind::curl($gateway . '?ak='.$ak.'&output=json&location='.implode(',', [$latitude, $longitude]));
		$result = json_decode($result);
		if($result->status == 'OK' || $result->status == '0') {
			return ['province' => $result->result->addressComponent->province,
					'city' => $result->result->addressComponent->city,
					'district' => $result->result->addressComponent->district];
		}
		
		return array();
	}
}