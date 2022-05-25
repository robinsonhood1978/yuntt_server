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
use common\models\WebimOnlineModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id WebimFriendForm.php 2018.10.20 $
 * @author mosir
 */
class WebimFriendForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		list($avatar, $username) = UserModel::getAvatarById(Yii::$app->user->id);
		$data = array('mine' => array('username' => $username, 'id' => Yii::$app->user->id, 'avatar' => $avatar, 'sign' => ''));
		
		// 列出好友
		if(Yii::$app->user->isGuest || !($logs = WebimLogModel::find()->select('fromid,toid')->where(['or', ['fromid' => Yii::$app->user->id], ['toid' => Yii::$app->user->id]])->orderBy(['logid' => SORT_DESC])->all())) {
			$data['friend'][] = array('groupname' => Language::get('my_friend'), 'id' => 1, 'list' => []);
			return array('code' => 0, 'msg'  => '', 'data' => $data);
		}
		
		// 好友数组
		$list = array();
		$friends = array();
				
		// 把自己也加入到好友列表去，如果不加的话， 会话只能单向
		$friends[] = Yii::$app->user->id;
		foreach($logs as $item)
		{
			if($item->fromid == Yii::$app->user->id) {
				$friends[] = $item->toid;
			} else $friends[] = $item->fromid;
		}
		foreach(($friends = array_unique($friends)) as $userid) 
		{
			// 如果好友当前不在线，则剔除（排除自己），30分钟内上线（或发过言的）都算在线
			if($userid != Yii::$app->user->id)
			{
				$online = WebimOnlineModel::find()->where(['userid' => $userid])->one();
				if(!$online) {
					if(Basewind::getCurrentApp() == 'pc') continue; // 只有PC端限制在线，移动端不限制是否在线
				}
				elseif($online->lasttime < Timezone::gmtime() - 1800) {
					continue;
				}
			}
			list($avatar, $username) = UserModel::getAvatarById($userid);
			
			// for WAP Only
			if(Basewind::getCurrentApp() == 'wap')
			{
				// 找出当前好友跟我的最后发言
				$lastTalk = WebimLogModel::find()->select('formatContent')->where(['fromid' => $userid, 'toid' => Yii::$app->user->id])->orderBy(['logid' => SORT_DESC])->scalar();
					
				// 找出这个好友发给我的未读的信息量
				$unread = $this->getTotalUnRead($userid, Yii::$app->user->id);
			}
			
			$list[] = ['id' => $userid, 'username' => $username, 'avatar' => $avatar, 'sign' => '', 'lastTalk' => $lastTalk, 'unread' => $unread];
		}
		$data['friend'][] = array('groupname' => Language::get('my_friend'), 'id' => 1, 'list' => $list);
		return array('code' => 0, 'msg'  => '', 'data' => $data);
	}
	
	/* 找出所有（或指定某个用户）发给我的未读信息总数 */
	public function getTotalUnRead($fromid = 0, $toid = 0)
	{
		$query = WebimLogModel::find()->where(['unread' => 1]);
		if($fromid) $query->andWhere(['fromid' => $fromid]);
		if($toid) $query->andWhere(['toid' => $toid]);
		
		return intval($query->count());
	}
}
