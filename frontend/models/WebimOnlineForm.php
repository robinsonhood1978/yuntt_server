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
use common\models\WebimOnlineModel;

use common\library\Timezone;

/**
 * @Id WebimOnlineForm.php 2018.10.20 $
 * @author mosir
 */
class WebimOnlineForm extends Model
{
	public $token = null;
	public $errors = null;
	
	public function formData($post = null)
	{
		$result = array();
		
		// 签名验证，防止人为恶意请求，导致上线用户不准确	
		if(md5($post->uid.$post->client_id.$this->token) != $post->sign) {
			return $result;
		}
			
		$now = Timezone::gmtime();
		if(($onid = WebimOnlineModel::find()->select('onid')->where(['userid' => $post->uid])->scalar())) {
			WebimOnlineModel::updateAll(['client_id' => $post->client_id, 'lasttime' => $now], ['onid' => $onid]);
		} else {
			$model = new WebimOnlineModel();
			$model->userid = $post->uid;
			$model->client_id = $post->client_id;
			$model->lasttime = $now;
			$model->save();
		}
		WebimOnlineModel::deleteAll(['<', 'lasttime', $now - 1800]);
			
		$list = WebimOnlineModel::find()->orderBy(['lasttime' => SORT_DESC])->all();
		foreach($list as $key => $item)
		{
			// 只取存在的用户的数据
			list($avatar, $username, $exists) = UserModel::getAvatarById($item->userid);
			if($exists)
			{	
				$userInfo = array('username' => $username, 'id' => $item->userid, 'avatar' => $avatar, 'type' => 'friend');
						
				$result['f_user'][$item->userid] = $item->client_id;
				$result['f_uuid'][$item->userid] = $item->userid;
				$result['f_uuser'][$item->userid]= $userInfo;
				
			}
		}
		return $result;
	}
}
