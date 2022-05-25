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

use common\models\DistributeModel;
use common\library\Page;

/**
 * @Id DistributeRankForm.php 2018.11.23 $
 * @author mosir
 */
class DistributeRankForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		$query = DistributeModel::find()->alias('d')->select('d.userid,d.amount,u.username,u.portrait')->joinWith('user u', false)->orderBy(['amount' => SORT_DESC, 'id' => SORT_ASC]);
			
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $val)
		{
			$list[$key]['portrait'] = empty($val['portrait']) ? Yii::$app->params['default_user_portrait'] : $val['portrait']; 
		}
		return array($list, $page);
	}
	
	public function myRank()
	{
		$query = DistributeModel::find()->select('amount')->where(['userid' => Yii::$app->user->id])->one();
		return DistributeModel::find()->select('did')->where(['<', 'amount', $query->amount])->orderBy(['money' => SORT_DESC])->count()+1;
	}
}
