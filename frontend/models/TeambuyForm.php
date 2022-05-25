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

use common\models\GoodsModel;
use common\models\AppmarketModel;
use common\models\ApprenewalModel;
use common\models\TeambuyModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;
use common\library\Def;

/**
 * @Id TeambuyForm.php 2019.10.7 $
 * @author mosir
 */
class TeambuyForm extends Model
{
	public $id = 0;
	public $store_id = null;
	public $errors = null;
	
	public function valid(&$post)
	{
		$result = array();
		
		if(($message = Promotool::getInstance('teambuy')->build(['store_id' => $this->store_id])->checkAvailable()) !== true) {
			$this->errors = $message;
			return false;
		}
	
        if (!$post->goods_id) {
            $this->errors = Language::get('fill_goods');
			return false;
        }
		if(TeambuyModel::find()->where(['goods_id' => $post->goods_id])->andWhere(['!=', 'id', $this->id])->exists()) {
			$this->errors = Language::get('goods_has_set_teambuy');
			return false;
		}
        if (empty($post->specs) || !is_object($post->specs)) {
            $this->errors = Language::get('fill_spec');
			return false;
        }
	
		// 目前只考虑打折类型的优惠
		foreach($post->specs as $key => $value) {
			if(!$value->discount || $value->discount >= 10 || $value->discount <= 0) {
				$this->errors = Language::get('invalid_price');
				return false;
			}

			$result[$key] = ['price' => $value->discount, 'type' => 'discount'];
		}
		if($result) {
			$post->specs = (object) $result;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->id || !($model = TeambuyModel::find()->where(['id' => $this->id, 'store_id' => $this->store_id])->one())) {
			$model = new TeambuyModel();
		}
		
		$model->title = $post->title ? $post->title : Language::get('twopeople');
		$model->goods_id = $post->goods_id;
		$model->specs = serialize(ArrayHelper::toArray($post->specs));
		$model->store_id = $this->store_id;
		$model->status = 1;
		$model->people = $post->people;
			
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
        return true;
	}
	
	public function queryInfo($id, $teambuy = array())
    {
		if(!$id && $teambuy) {
			$id = $teambuy['goods_id'];
		}
		
		$goods = GoodsModel::find()->select('goods_id,goods_name,spec_name_1,spec_name_2,spec_qty,default_spec,default_image')->with('goodsSpec')->where(['goods_id' => $id, 'store_id' => $this->store_id])->asArray()->one();
		if(!$goods) {
			return false;
		}
		
		empty($goods['default_image']) && $goods['default_image'] = Yii::$app->params['default_goods_image'];
		
        if ($goods['spec_qty'] == 1 || $goods['spec_qty'] == 2) {
            $goods['spec_name'] = htmlspecialchars($goods['spec_name_1'] . ($goods['spec_name_2'] ? ' ' . $goods['spec_name_2'] : ''));
        }
        else {
            $goods['spec_name'] = Language::get('spec');
        }
		
        foreach ($goods['goodsSpec'] as $key => $spec)
        {	
            if ($goods['spec_qty'] == 1 || $goods['spec_qty'] == 2) {
                $goods['goodsSpec'][$key]['spec'] = htmlspecialchars($spec['spec_1'] . ($spec['spec_2'] ? ' ' . $spec['spec_2'] : ''));
			}
		    else {
                $goods['goodsSpec'][$key]['spec'] = Language::get('default_spec');
            }
			
			if($teambuy) {
				$goods['goodsSpec'][$key]['pro_price'] = $teambuy['specs'][$spec['spec_id']]['price'];
			}
        }
        return $goods;
    }
}
