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

/**
 * @Id SessionStorage.php 2018.7.4 $
 * @author mosir
 */
 
class SessionStorage implements StorageInterface
{
    /**
     * @var array $params Custom configuration params
     */
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return CartItem[]
     */
    public function load()
    {
        return Yii::$app->session->get($this->params['key'], []);
    }

    /**
     * @param CartItem[] $items
     * @return void
     */
    public function save(array $items)
    {
        Yii::$app->session->set($this->params['key'], $items);
    }
}
