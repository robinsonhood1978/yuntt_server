<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_integral_article;

use Yii;

use common\models\ArticleModel;
use common\models\AcategoryModel;
use common\models\UserModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */

class Df_integral_articleWidget extends BaseWidget
{
    var $name = 'df_integral_article';

    public function getData()
    {
		return array(
			'article'			=> $this->getArticles(2),
			'user_login_status' => Yii::$app->user->isGuest ? false : true,
			'user_info'         => $this->getUserInfo(),
		);
    }

    public function parseConfig($input)
    {
        return $input;
    }
	public function getConfigDataSrc()
    {
		// 取得二级文章分类
		$this->params['acategories'] = $this->getAcategoryOptions(0, -1, null, 2);
    }
	public function getArticles($num = 2)
	{
		$query = ArticleModel::find()->select('article_id, title, add_time')->where(['if_show' => 1]);
		
		$allId = AcategoryModel::getDescendantIds(intval($this->options['cate_id']));
		if($allId) {
			$query->andWhere(['in', 'cate_id', $allId]);
		}
		return $query->orderBy(['sort_order' => SORT_ASC, 'add_time' => SORT_DESC])->limit($num)->asArray()->all();
	}
	
	public function getUserInfo()
	{
		$query = array();
		if(!Yii::$app->user->isGuest) {
			$query = UserModel::find()->alias('u')->select('u.userid,u.username,u.portrait,i.amount')->joinWith('integral i', false)->where(['u.userid' => Yii::$app->user->id])->asArray()->one();
		}
		if(empty($query['portrait'])) $query['portrait'] = Yii::$app->params['default_user_portrait'];
		
		return $query;
	}
}