<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\components\cart;

use yii;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\base\Component;

use common\components\cart\CartItem;

/**
 * @Id Cart.php 2018.7.4 $
 * @author mosir
 */
 
class Cart extends Component
{
	/**
     * @var string $storageClass
     */
    public $storageClass = 'common\components\cart\storage\DbSessionStorage';

    /**
     * @var array $params Custom configuration params
     */
    public $params = [];

    /**
     * @var array $defaultParams
     */
    private $defaultParams = [
        'key' => 'cart',
        'expire' => 604800
    ];

    /**
     * @var CartItem[]
     */
    private $items;
	
	/**
     * @var \common\library\cart\storage\StorageInterface
     */
    private $storage;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->params = array_merge($this->defaultParams, $this->params);

        if (!class_exists($this->storageClass)) {
            throw new InvalidConfigException('storageClass `' . $this->storageClass . '` not found');
        }

        $this->storage = new $this->storageClass($this->params);
    }
	
	/**
	 * create an cart item
	 */
	public function createItem($params = array())
	{
		return CartItem::createProduct($params);		
	}
	
	/**
	 * move item to cart from session
	 */
	public function move()
	{
		$this->loadItems();
	}
	
	/**
	 * you can set any identification ID that is considered the same product in a shopping cart  
     * @param array $params
	 */
	public function getId($params = array())
	{
		// 注意避免数值型转为字符型的情况,不要加入价格字段
		return md5(serialize([intval($params['spec_id']), /*floatval($params['price']),*/ intval(Yii::$app->user->id)]));
	}
	
	/**
     * put an item to the cart
     * @param object $product
     * @param integer $quantity
     * @return void
     */
    public function put($product, $quantity)
    {
        $this->loadItems();
		if (isset($this->items[$product->product_id])) {
            $this->plus($product->product_id, $quantity);
        } else {
            $this->add($product, $quantity);
        }
    }

	/**
     * Add an item to the cart
     * @param object $product
     * @param integer $quantity
     * @return void
     */
    private function add($product, $quantity)
    {
		$this->items[$product->product_id] = new CartItem($product, $quantity, $this->params);
		$this->saveItems();
    }

	/**
     * Adding item quantity in the cart
     * @param integer $id
     * @param integer $quantity
     * @return void
     */
    private function plus($id, $quantity)
    {
 		$this->items[$id]->setQuantity($quantity + $this->items[$id]->getQuantity());
        $this->saveItems();
    }

    /**
     * Change item quantity/price in the cart
     * @param integer $id
     * @param integer $quantity
     * @param float $price
     * @return bool true|false
     */
    public function change($id, $quantity, $price = null)
    {
        $this->loadItems();
        if (isset($this->items[$id])) {
            $this->items[$id]->setQuantity($quantity);
            if($price !== null) $this->items[$id]->setPrice($price);
			$this->saveItems();
			return true;
        }
        return false;
    }
	
	/**
     * chose item in the cart
     * @param integer $id
     * @param integer $selected
     * @return bool true|false
     */
    public function chose($id, $selected = 1)
    {
        $this->loadItems();
        if (isset($this->items[$id])) {
            $this->items[$id]->getProduct()->selected = intval($selected);
			$this->saveItems();
			return true;
        }
        return false;
    }
    
	/**
     * unchose all items in the cart
     * @return void
     */
    public function unchoses()
    {
        $this->loadItems();
		foreach($this->items as $id => $item) {
			$this->items[$id]->getProduct()->selected = 0;
		}
        $this->saveItems();
    }

    /**
     * Removes an items from the cart
     * @param integer $id
     * @return bool true|false
     */
    public function remove($id)
    {
        $this->loadItems();
        if (array_key_exists($id, $this->items)) {
            unset($this->items[$id]);
			$this->saveItems();
			return true;
        }
        return false;
    }

    /**
     * Removes all items from the cart
     * @return void
     */
    public function clear()
    {
        $this->items = [];
        $this->saveItems();
    }

    /**
     * Returns all items from the cart
     * @return CartItem[]
     */
    public function getItems()
    {
        $this->loadItems();
        return $this->items;
    }

    /**
     * Returns an item from the cart
     * @param integer $id
     * @return CartItem
     */
    public function getItem($id)
    {
        $this->loadItems();
        return isset($this->items[$id]) ? $this->items[$id] : new CartItem(null, 0, $this->params);
    }

    /**
     * Returns ids array all items from the cart
     * @return array
     */
    public function getItemIds()
    {
        $this->loadItems();
        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->getProduct()->product_id;
        }
        return $items;
    }

    /**
     * Returns total cost all items from the cart
     * @return integer
     */
    public function getTotalPrice()
    {
        $this->loadItems();
		$cost = 0;
        foreach ($this->items as $item) {
            $cost += $item->getSubtotal();
        }
        return sprintf('%.2f', round($cost, 2));
    }

    /**
     * Returns total count all items from the cart
     * @return integer
     */
    public function getTotalCount()
    {
        $this->loadItems();
		$count = 0;
        foreach ($this->items as $item) {
			$count += $item->getQuantity();
        }
        return $count;
    }
	
	public function getTotalKinds()
	{
		$this->loadItems();
		return $this->items ? count($this->items) : 0;
	}

    /**
     * Load all items from the cart
     * @return void
     */
    private function loadItems()
    {
        if ($this->items === null) {
            $this->items = $this->storage->load();
        }
    }

    /**
     * Save all items to the cart
     * @return void
     */
    private function saveItems()
    {
        $this->storage->save($this->items);
    }
	
	/**
	 * Returns items for cart page 
	 */
	public function find()
	{
		$products = [];
		if(($items = $this->getItems())) {
			foreach($items as $key => $cartItem) {
				$product = ArrayHelper::toArray($cartItem->getProduct());
				$product['quantity'] = $cartItem->getQuantity();
				
				// don't use object as: $product->subtotal
				$product['subtotal'] = sprintf('%.2f', round($product['price'] * $product['quantity'], 2));
				$products[$key] = $product;
			}
		}
		return array(
			'amount' 	=> $this->getTotalPrice(),
			'kinds' 	=> count($products), // $this->getTotalKinds() for $products is object
			'items' 	=> $products
		);
	}
	
	/**
	 * Return item for update quantity in cart page
	 */
	public function get($id)
	{
		if(($cartItem = $this->getItem($id))) {
			$product = ArrayHelper::toArray($cartItem->getProduct());
			$product['quantity'] = $cartItem->getQuantity();
			$product['subtotal'] = sprintf('%.2f', round($product['price'] * $product['quantity'], 2));
		}
		return array(
			'amount'	=> $this->getTotalPrice(),
			'kinds' 	=> $this->getTotalKinds(),
			'item' 		=> $product
		);
	}
}