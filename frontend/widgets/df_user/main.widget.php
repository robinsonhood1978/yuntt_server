<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_user;

use Yii;

use common\models\AcategoryModel;
use common\models\ArticleModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_userWidget extends BaseWidget
{
    var $name = 'df_user';

    public function getData()
    {
		$visitor = array(
			'userid' => Yii::$app->user->id, 
			'username' => 'Hi~欢迎 ' . Yii::$app->user->identity->username,
			'portrait' => Yii::$app->user->identity->portrait
		);
		!$visitor['portrait'] && $visitor['portrait'] = Yii::$app->params['default_user_portrait'];
		!$visitor['userid'] && $visitor['username'] = '您好，欢迎光临';
			
      	$data = array(	
			'model_id' 		=> mt_rand(),	
			'articleList'	=> $this->getList(),
			'visitor'		=> $visitor					
		);

        return $data;	
    }
	
	public function getList()
	{
		$list = array();
		for($i = 1; $i <= 2; $i++)
		{
			$list[] = array(
				'title'	=> $this->options['model'.$i.'_title'],
				'list'	=> $this->getArticles(intval($this->options['cate_id_'.$i])),
			);
		}	
		return $list;
	}
	
	public function getConfigDatasrc()
    {
		// 取得二级文章分类
		$this->params['acategories'] = AcategoryModel::getOptions(0, -1, null, 2);	
    }
		
	public function getArticles($cate_id = 0, $num = 3)
    {
		$query = ArticleModel::find()->select('article_id,title')->where(['if_show' => 1]);
		if(($allId = AcategoryModel::getDescendantIds($cate_id))) {
			$query->andWhere(['in', 'cate_id', $allId]);
		}
		return $query->limit($num)->orderBy(['sort_order' => SORT_ASC, 'add_time' => SORT_DESC])->asArray()->all();
	}
}
