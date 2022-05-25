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

/**
 * @Id StorageInterface.php 2018.7.4 $
 * @author mosir
 */
 
interface StorageInterface
{
    /**
     * @param array $params (configuration params)
     */
    public function __construct(array $params);
    /**
     * @return CartItem[]
     */
    public function load();
    /**
     * @param CartItem[] $items
     */
    public function save(array $items);
}
