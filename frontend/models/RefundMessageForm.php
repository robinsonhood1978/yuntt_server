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

use common\models\RefundModel;
use common\models\RefundMessageModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id RefundMessageForm.php 2018.10.17 $
 * @author mosir
 */
class RefundMessageForm extends Model
{
	public $errors = null;
	
	/**
	 * 兼容API接口获取数据
	 */
	public function formData($post = null, $pageper = 10, $isAJax = false, $curPage = false)
	{
		$query = RefundMessageModel::find()->where(['refund_id' => $post->id])->orderBy(['created' => SORT_DESC]);

		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $val)
		{
			if($val['owner_id'] == Yii::$app->user->id) $sender = Language::get('self');
			else $sender = Language::get($val['owner_role']);
			$recordlist[$key]['sender'] = $sender;
			
			if(in_array(Basewind::getCurrentApp(), ['wap', 'api'])) {
				$recordlist[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $val['created']);
			}
		}
					
		return array($recordlist, $page);
	}
	
	public function valid($post = null)
	{
		if(!$post->id || !($refund = RefundModel::find()->where(['refund_id' => $post->id])->andWhere(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->asArray()->one())) {
			$this->errors = Language::get('no_such_refund');
			return false;
		}

		// 关闭或者是成功的退款，不能添加留言
		if(in_array($refund['status'], array('SUCCESS', 'CLOSED'))){
			$this->errors = Language::get('add_refund_message_not_allow');
			return false;
		}
		
		if(empty($post->content)) {
			$this->errors = Language::get('refund_message_empty');
			return false;
		}
		
		return true;
	}
	
	public function save($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$refund = RefundModel::find()->select('buyer_id,seller_id')->where(['refund_id' => $post->id])->one();
		
		$model = new RefundMessageModel();
		$model->owner_id = Yii::$app->user->id;
		$model->owner_role = $refund->buyer_id == Yii::$app->user->id ? 'buyer' : ($refund->seller_id == Yii::$app->user->id ? 'seller' : 'admin');
		$model->refund_id = $post->id;
		$model->content = $post->content;
		$model->created = Timezone::gmtime();
		
		if(($image = UploadedFileModel::getInstance()->upload('image', 0, Def::BELONG_REFUND_MESSAGE, $post->id))) {
			$model->image = $image;
		}
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('add_fail');
			return false;
		}
		
		return true;
	}
}
