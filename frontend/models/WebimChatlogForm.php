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

use common\library\Page;

/**
 * @Id WebimChatlogForm.php 2018.10.20 $
 * @author mosir
 */
class WebimChatlogForm extends Model
{
	public $errors = null;
	
	public function formData(&$post = null, $pageper = 8)
	{
		$post->type = ($post->type == 'group') ? $post->type : 'friend';
		
		$query = WebimLogModel::find()->select('logid,add_time,formatContent,fromid,fromName,toid,toName')->where(['and', ['type' => $post->type], ['or', ['fromid' => Yii::$app->user->id, 'toid' => $post->id], ['fromid' => $post->id, 'toid' => Yii::$app->user->id]]])->orderBy(['logid' => SORT_ASC])->indexBy('logid');

		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($list as $key => $item)
		{
			list($avatar, $username) = UserModel::getAvatarById($item['fromid']);
			$list[$key]['avatar'] = $avatar;
		}
		
		// 将显示出来的数据全部设置为已读（即：减少“未读”的数量）
		WebimLogModel::updateAll(['unread' => 0], ['in', 'logid', array_keys($list)]);
		
		return array($list, $page);
	}
}
