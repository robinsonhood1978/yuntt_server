<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\DistributeMerchantModel;
use common\library\Page;

/**
 * @Id DistributeTeamForm.php 2018.11.23 $
 * @author luckey
 */
class DistributeTeamForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4, $isAJax = false, $curPage = false) 
	{
		$post->id = $post->id ? $post->id : Yii::$app->user->id;

		// 找出当前用户的所有下级团队
		$childs = DistributeMerchantModel::getChilds($post->id);

		$query = DistributeMerchantModel::find()->alias('dm')->select('dm.userid,dm.username,u.portrait')->joinWith('user u', false)->where(['in', 'dm.userid', $childs])->orderBy(['created' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value)
		{
			$list[$key]['portrait'] = empty($val['portrait']) ? Yii::$app->params['default_user_portrait'] : $value['portrait']; 
			$list[$key]['childcount'] = DistributeMerchantModel::find()->select('dmid')->where(['parent_id' => $value['userid']])->count();
		}
		return array($list, $page);
	}
}
