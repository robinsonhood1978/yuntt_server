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
 * @Id AcategoryModel.php 2018.8.22 $
 * @author mosir
 */

class AcategoryModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%acategory}}';
	}
	
	/**
     * 取得分类列表
     * @param int $parent_id 大于等于0表示取某分类的下级分类，小于0表示取所有分类
     * @param int $store_id  店铺编号
     * @param bool $shown    只取显示的分类
     */
    public static function getList($parent_id = -1, $store_id = 0, $shown = true)
    {
		if(!$store_id) $store_id = 0;
		$query = parent::find()->where(['store_id' => $store_id]);
		if($parent_id >= 0) $query->andWhere(['parent_id' => $parent_id]);
		if($shown) $query->andWhere(['if_show' => 1]);
		
		return $query->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC])->asArray()->all();
    }
	
	/* 取得所有文章分类 
	 * 保留级别缩进效果，一般用于select
	 * @return array(21 => 'abc', 22 => '&nbsp;&nbsp;');
	 */
    public static function getOptions($store_id = 0, $parent_id = -1, $except = NULL, $layer = 0, $shown = true, $space = '&nbsp;&nbsp;')
    {
		$acategories = self::getList($parent_id, $store_id, $shown);
		
		$tree = new Tree();
		$tree->setTree($acategories, 'cate_id', 'parent_id', 'cate_name');
			
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
	public static function getDescendantIds($id = 0, $store_id = 0, $cached = true, $shown = false, $selfin = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$conditions = $shown ? ['store_id' => $store_id, 'if_show' => 1] : ['store_id' => $store_id];
			
			$tree = new Tree();
			$data = $tree->recursive(new AcategoryModel(), $conditions)->getArrayList($id)->fields($selfin);
						
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
	
	 /**
     * 取得某分类的祖先分类（包括自身，按层级排序）
     *
     * @param   int  $id       分类id
	 * @param 	bool $shown    只取要显示的分类
     * @param   bool $cached   是否取缓存
     * @return  array(
     *              array('cate_id' => 1, 'cate_name' => '数码产品'),
     *              array('cate_id' => 2, 'cate_name' => '手机'),
     *              ...
     *          )
     */
    public static function getAncestor($id, $store_id = 0, $shown = true, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$data = array();
			$query = parent::find()->select('cate_id,cate_name,parent_id')->where(['cate_id' => $id, 'store_id' => $store_id]);
			if($shown) $query->andWhere(['if_show' => 1]);
			$acategory = $query->asArray()->one();
			if($acategory) {
				$data[] = $acategory;
			}
			
			while($acategory && ($acategory['parent_id'] > 0)) 
			{
				$query = parent::find()->select('cate_id,cate_name,parent_id')->where(['cate_id' => $acategory['parent_id'], 'store_id' => $store_id]);
				if($shown) $query->andWhere(['if_show' => 1]);
				$acategory = $query->asArray()->one();
					
				$data[] = $acategory;
			}
			$cache->set($cachekey, $data, 3600);
		}
		return array_reverse($data);
	}
}
