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

use common\models\IntegralModel;
use common\models\OrderModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Language;
use common\library\Page;

/**
 * @Id My_integralLogsForm.php 2018.9.19 $
 * @author mosir
 */
class My_integralLogsForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			$this->errors = Language::get('integral_disabled');
			return false;
		}
		
		$query = IntegralLogModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['log_id' => SORT_DESC]);
		if(in_array($post->type, ['income'])) {
			$query->andWhere(['>', 'changes', 0])->andWhere(['state' => 'finished']);
		} elseif(in_array($post->type, ['pay'])) {
			$query->andWhere(['<', 'changes', 0])->andWhere(['state' => 'finished']);
		} elseif(in_array($post->type, ['frozen'])) {
			$query->andWhere(['state' => 'frozen']);
		}
			
		$page = Page::getPage($query->count(), $pageper);
		$integralLogs = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($integralLogs as $key => $val)
		{
			$integralLogs[$key]['name'] = Language::get($val['type']);
			
			if(Basewind::getCurrentApp() == 'wap') {
				$integralLogs[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
			}
			elseif(Basewind::getCurrentApp() == 'pc') {
				$integralLogs[$key]['state'] = IntegralModel::getStatusLabel($val['state']);
				$integralLogs[$key]['order_sn'] = OrderModel::find()->select('order_sn')->where(['order_id' => $val['order_id']])->scalar();
			}
		}
		return array($integralLogs, $page);
	}
}
