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

use common\library\Language;
use common\library\Tree;

/**
 * @Id WeixinMenuModel.php 2018.8.27 $
 * @author mosir
 */

class WeixinMenuModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%weixin_menu}}';
	}
	
	/* 判断名称是否唯一 */
    public static function unique($name, $parent_id = 0, $id = 0, $userid = 0)
    {
		$query = parent::find()->where(['name' => $name]);
		if($parent_id >= 0) $query->andWhere(['parent_id' => $parent_id]);
		if($userid >= 0) $query->andWhere(['userid' => $userid]);
		if($id > 0) $query->andWhere(['!=', 'id', $id]);
		return $query->exists() ? false : true;
    }
	
	/* 验证菜单名称长度和菜单个数的合法性 */
	public function checkName($name, $parent_id = 0, $id = 0, $userid = 0)
	{
		$query = parent::find()->select('id')->where(['parent_id' => $parent_id]);
		if($userid >= 0) $query->andWhere(['userid' => $userid]);
		if($id > 0) $query->andWhere(['!=', 'id', $id]);
		
		$count = $query->count();
		$len = iconv_strlen($name, Yii::$app->charset);
		
		if(!$parent_id) 
		{
			if($count >= 3){
				$this->addError('menu', Language::get('menu_gt_3'));
				return false;
			}
			elseif($len > 4){
				$this->addError('name', Language::get('name_gt_4'));
				return false;
			}
		}
		else 
		{
			if($count >= 5){
				$this->addError('menu', Language::get('menu_gt_5'));
				return false;
			}
			elseif($len > 8){
				$this->addError('name', Language::get('name_gt_8'));
				return false;
			}
		}
		return true;		
	}
	
	/**
     * 取得栏目列表
     * @param int $parent_id 大于等于0表示取某栏目的下级栏目，小于0表示取所有栏目
     * @param int $userid  用户编号
     */
    public static function getList($parent_id = -1, $userid = 0)
    {
		if(!$userid) $userid = 0;
		$query = parent::find()->where(['userid' => $userid]);
		if($parent_id >= 0) $query->andWhere(['parent_id' => $parent_id]);
		
		return $query->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
    }
	
	/**
     * 取得某栏目的子孙栏目id
     * @param int  $id     栏目id
     * @param bool $cached 是否缓存
	 * @param bool $selfin 是否包含自身id
	 * @return array(1,2,3,4...)
	 */
	public static function getDescendantIds($id = 0, $userid = 0, $cached = true, $selfin = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$tree = new Tree();
			$data = $tree->recursive(new WeixinMenuModel(), ['userid' => $userid])->getArrayList($id, 'id', 'parent_id', 'name')->fields($selfin);
						
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
	
	public static function getMenus($parent_id = 0, $userid = 0)
	{
		if(!($list = self::getList($parent_id, $userid))) {
			return false;
		}
		
		$menus = array();
		foreach(array_values($list) as $key => $val)
		{
			$menus['button'][$key]['name'] = urlencode($val['name']); 
			if(($child = self::getList($val['id'], $userid)))
			{
				foreach(array_values($child) as $k => $v)
				{
					$menus['button'][$key]['sub_button'][$k]['name'] = urlencode($v['name']);
					$menus['button'][$key]['sub_button'][$k]['type'] = $v['type'];
					if($v['type'] == 'view') {
						$menus['button'][$key]['sub_button'][$k]['url'] = $v['link'];
					}
					else {
						$menus['button'][$key]['sub_button'][$k]['key'] = $v['reply_id'];
					}
				}
			}
			else
			{
				$menus['button'][$key]['type'] = $val['type'];
				if($val['type'] == 'view') {
					$menus['button'][$key]['url'] = $val['link'];
				}
				else {
					$menus['button'][$key]['key'] = $val['reply_id'];
				}
			}
		}
		return stripslashes(urldecode(json_encode($menus)));
	}
}
