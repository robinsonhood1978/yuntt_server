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

use common\models\UserModel;
use common\models\WebimLogModel;

use common\library\Timezone;

/**
 * @Id WebimLastchatForm.php 2018.10.20 $
 * @author mosir
 */
class WebimLastchatForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $limit = 20)
	{
		$post->type = ($post->type == 'group') ? $post->type : 'friend';
		$post->limit =  $post->limit > 0 ? $post->limit : $limit;
		
		$result = $allId = array();
		$list = WebimLogModel::find()->select('logid,add_time,content,fromid,fromName,toid')->where(['and', ['type' => $post->type], ['or', ['fromid' => Yii::$app->user->id, 'toid' => $post->id], ['fromid' => $post->id, 'toid' => Yii::$app->user->id]]])->orderBy(['logid' => SORT_DESC])->indexBy('logid')->limit($post->limit)->all();
		
		// 排序，让最后发言的在后面
		array_multisort($list, SORT_ASC);
		foreach($list as $item)
		{
			list($avatar, $username) = UserModel::getAvatarById($item->fromid);
			
			$result[] = array(
				'avatar'	=> $avatar,
				'content'	=> $item->content,
				'id'		=> $item->toid,
				'timestamp'	=> Timezone::localDate('Y-m-d H:i:s', $item->add_time),
				'type'		=> 'friend',
				'username'	=> $item->fromName,
				'mine'		=> $item->toid == $post->id ? true : false
			);
			$allId[] = $item->logid;
		}
		
		// 将显示出来的数据全部设置为已读（即：减少“未读”的数量）
		WebimLogModel::updateAll(['unread' => 0], ['in', 'logid', $allId]);
		
		return $result;
	}
}
