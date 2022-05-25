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

use common\library\Tree;

/**
 * @Id ScategoryModel.php 2018.4.2 $
 * @author mosir
 */

class ScategoryModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%scategory}}';
    }
	
	/**
     * 取得分类列表
     * @param int $parent_id 大于等于0表示取某分类的下级分类，小于0表示取所有分类
     * @param bool $shown    只取显示的分类
     */
    public static function getList($parent_id = -1, $shown = true)
    {
		$query = parent::find();
		if($parent_id >= 0) $query->where(['parent_id' => $parent_id]);
		if($shown) $query->andWhere(['if_show' => 1]);
		
		return $query->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC])->asArray()->all();
    }
	
	/* 所有店铺类目，树结构 */
	public static function getTree($shown = true, $layer = 0,  $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$scategories = self::getList(-1, $shown);
		
			$tree = new Tree();
			$tree->setTree($scategories, 'cate_id', 'parent_id', 'cate_name');
			$data = $tree->getArrayList(0, $layer);
		
    		//第二个参数即是我们要缓存的数据 
    		//第三个参数是缓存时间，如果是0，意味着永久缓存。默认是0 
    		$cache->set($cachekey, $data, 3600);
		} 

		return $data;
	}
	
	/* 取得所有分类 
	 * 保留级别缩进效果，一般用于select
	 * @return array(21 => 'abc', 22 => '&nbsp;&nbsp;');
	 */
    public static function getOptions($parent_id = -1, $except = NULL, $layer = 0, $shown = true, $space = '&nbsp;&nbsp;')
    {
		$scategories = self::getList($parent_id, $shown);
		
		$tree = new Tree();
		$tree->setTree($scategories, 'cate_id', 'parent_id', 'cate_name');
			
        return $tree->getOptions($layer, 0, $except, $space);
    }
	
	/**
     * 取得某分类的子孙分类id
     * @param int  $id     分类id
     * @param bool $cached 是否缓存
	 * @param bool $shown  只取要显示的分类
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
			$data = $tree->recursive(new ScategoryModel(), $conditions)->getArrayList($id)->fields($selfin);
						
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
}
