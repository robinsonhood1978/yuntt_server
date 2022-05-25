<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\components\cart\storage;

use Yii;
use yii\helpers\ArrayHelper;

use common\components\cart\CartItem;
use common\components\cart\storage\SessionStorage;

use common\models\CartModel;

/**
 * @Id DbSessionStorage.php 2018.7.4 $
 * @author mosir
 */
 
class DbSessionStorage implements StorageInterface
{
    /**
     * @var array $params Custom configuration params
     */
    private $params;

    /**
     * @var integer $userId
     */
    private $userId;

    /**
     * @var SessionStorage $sessionStorage
     */
    private $sessionStorage;

    public function __construct(array $params)
    {
        $this->params = $params;
        $this->userId = Yii::$app->user->id;
        $this->sessionStorage = new SessionStorage($this->params);
    }

    /**
     * @return CartItem[]
     */
    public function load()
    {
        if (Yii::$app->user->isGuest) {
            return $this->sessionStorage->load();
        }
        $this->moveItems();
        return $this->loadDb();
    }

    /**
     * @param CartItem[] $items
     * @return void
     */
    public function save(array $items)
    {
        if (Yii::$app->user->isGuest) {
            $this->sessionStorage->save($items);
        } else { 
            $this->moveItems();
            $this->saveDb($items);
        }
    }

    /**
     *  Moves all items from session storage to database storage
     * @return void
     */
    private function moveItems()
    {
        if ($sessionItems = $this->sessionStorage->load()) {
            $items = array_merge($this->loadDb(), $sessionItems);
            $this->saveDb($items);
            $this->sessionStorage->save([]);
        }
    }

    /**
     * Load all items from the database
     * @return CartItem[]
     */
    private function loadDb()
    {
		$items = [];
		$carts = CartModel::find()->select('rec_id,userid,store_id,goods_id,goods_name,spec_id,specification,price,quantity,goods_image,product_id,selected')->where(['userid' => $this->userId])->all();
		foreach($carts as $product) {
			$items[$product->product_id] = new CartItem($product, $product->quantity, $this->params);
		}
		return $items;
    }

    /**
     * Save all items to the database
     * @param CartItem[] $items
     * @return void
     */
    private function saveDb(array $items)
    {
		$id = [];
		foreach($items as $cartItem)
		{
			if(($product = $cartItem->getProduct())) 
			{
				if($product->store_id == $this->userId) continue;
				
				if(!($model = CartModel::find()->where(['userid' => $this->userId, 'spec_id' => $product->spec_id])->one())) {
					$model = new CartModel();
				}
				
				foreach($product as $key => $val) {
					$model->$key = $val;
				}
				// session data to AR cart Model
				$model->product_id = Yii::$app->cart->getId(ArrayHelper::toArray($product));
				$model->userid = $this->userId;
				$model->quantity = $cartItem->getQuantity();
				$model->save() && $id[] = $model->rec_id;
			}
		}
		
		CartModel::deleteAll(['and', ['userid' => $this->userId], ['not in', 'rec_id', $id]]);
	}
}
