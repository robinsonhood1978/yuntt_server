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

use Yii;

/**
 * @Id CartItem.php 2018.7.4 $
 * @author mosir
 */
 

class CartItem
{
    /**
     * @var object $product
     */
    private $product;
	
    /**
     * @var integer $quantity
     */
    private $quantity;
	
    /**
     * @var array $params Custom configuration params
     */
    private $params;

    public function __construct($product, $quantity, array $params)
    {
        $this->product = $product;
        $this->quantity = $quantity;
        $this->params = $params;
    }

    /**
     * Returns the price of the item
     * @return integer|float
     */
    public function getPrice()
    {
        return $this->product->price;
    }

    /**
     * Returns the product, AR model
     * @return object
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Returns the subtotal of the item
     * @return integer|float
     */
    public function getSubtotal()
    {
        return round($this->getPrice() * $this->quantity, 2);
    }

    /**
     * Returns the quantity of the item
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Sets the quantity of the item
     * @param integer $quantity
     * @return void
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * Sets the price of the item
     * @param integer $price
     * @return void
     */
    public function setPrice($price) 
    {
        $this->product->price = $price;
    }
	
	public static function createProduct($params = array())
	{
		$spec_1 = $params['spec_name_1'] ? $params['spec_name_1'] . ':' . $params['spec_1'] : $params['spec_1'];
		$spec_2 = $params['spec_name_2'] ? $params['spec_name_2'] . ':' . $params['spec_2'] : $params['spec_2'];
	
		$specification = trim($spec_1 . ' ' . $spec_2);
		$goods_image = $params['spec_image'] ? $params['spec_image'] : $params['default_image'];
	
		$product = array(
			'userid'		=> intval(Yii::$app->user->id),
			'store_id'      => $params['store_id'],
			'goods_id'      => $params['goods_id'],
			'goods_name'    => addslashes($params['goods_name']),
			'price'         => round($params['price'], 2),
			'product_id'    => $params['product_id'],
			'spec_id'       => $params['spec_id'],
			'specification' => addslashes($specification),
			'goods_image'   => addslashes($goods_image),
			'selected'		=> isset($params['selected']) ? intval($params['selected']) : 0,
		);
		return (object)$product;
	}
}
