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
use yii\helpers\ArrayHelper;

use common\models\UserModel;
use common\models\MessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_messageForm.php 2018.10.3 $
 * @author mosir
 */
class My_messageForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4) 
	{		
		$query = MessageModel::find()->where(['parent_id' => 0])->orderBy(['last_update' => SORT_DESC, 'msg_id' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $record)
		{
			if(Basewind::getCurrentApp() == 'wap') {
		  		$recordlist[$key]['last_update'] = Timezone::localDate('Y-m-d H:i:s', $record['last_update']);
			}
			
			//判断是否是新消息
			if((($record['from_id'] == Yii::$app->user->id && $record['new'] == 2) || ($record['to_id'] == Yii::$app->user->id && $record['new'] == 1 ))) {
				$recordlist[$key]['new'] = 1;
			}

			$user = array();
            if ($record['from_id'] == 0 && $record['to_id'] == 0) {
				$user['username'] = Language::get('announce_msg');
				$user['portrait'] = Yii::$app->params['default_user_portrait'];
            }
            elseif ($record['from_id'] == 0) {
                $user['username'] = Language::get('system_msg');
				$user['portrait'] = Yii::$app->params['default_user_portrait'];
            }
			else {
				$visitorId = ($record['to_id'] == Yii::$app->user->id) ? $record['from_id'] : $record['to_id'];
				$user = UserModel::find()->select('userid,username,portrait')->where(['userid' => $visitorId])->asArray()->one();
			}
            $recordlist[$key]['user'] = $user;
		}
		return array($recordlist, $page);
	}

	public function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['folder'])) {
					return true;
				}
			}
			return false;
		}
		
		switch ($post->folder)
        {
			case 'privatepm':
				$query->andWhere(['or', ['and', ['from_id' => Yii::$app->user->id], ['in', 'status', [2,3]]], ['and', ['to_id' => Yii::$app->user->id], ['in', 'status', [1,3]], ['>', 'from_id', 0]]]); 
      		break;
            case 'systempm':
				$query->andWhere(['from_id' => 0, 'to_id' => Yii::$app->user->id]);
            break;
            case 'announcepm':
				$query->andWhere(['from_id' => 0, 'to_id' => 0]);
            break;
            default:
				$query->andWhere(['or', ['and', ['new' => 1], ['in', 'status', [1,3]], ['to_id' => Yii::$app->user->id]], ['and', ['new' => 2, 'from_id' => Yii::$app->user->id], ['in', 'status', [2,3]]]]);
            break;
		}
	
		return $query;
	}
}
