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

use common\models\WebimLogModel;
use common\models\WebimOnlineModel;

use common\library\Timezone;

/**
 * @Id WebimTalkForm.php 2018.10.20 $
 * @author mosir
 */
class WebimTalkForm extends Model
{
	public $token = null;
	public $errors = null;
	
	public function save($post = null)
	{
		$post->fromName = stripslashes($post->fromName);
		$post->toName = stripslashes($post->toName);
		$post->content = stripslashes($post->content);
		$post->formatContent = unserialize(stripslashes($post->formatContent));

		$result = 0;
		if(!empty($post->content) && ($post->sign = md5($post->from.$post->to.$post->type.$post->content.$this->token)))
		{
			$model = new WebimLogModel();
			$model->fromid = $post->from;
			$model->fromName = $post->fromName;
			$model->toid = $post->to;
			$model->toName = $post->toName;
			$model->type = $post->type;
			$model->content = $post->content;
			$model->formatContent = $post->formatContent;
			$model->add_time = Timezone::gmtime();
			if($model->save())
			{
				// 用户发言后，即更新在线用户时间（以免用户30分钟内不刷新页面，导致被认为是离线用户删除处理
				WebimOnlineModel::updateAll(['lasttime' => Timezone::gmtime()], ['userid' => $post->from]);
				$result = $model->logid;
			}
		}
		return $result;
	}
}
