<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\editor\kindeditor;

use yii;

use common\plugins\BaseEditor;
use common\plugins\editor\kindeditor\SDK;

/**
 * @Id kindeditor.plugin.php 2018.9.5 $
 * @author mosir
 */

class Kindeditor extends BaseEditor
{
	/**
     * 插件实例
	 * @var string $code
	 */
	public $code = 'kindeditor';

	/**
     * SDK实例
	 * @var object $client
     */
	private $client = null;

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
     * 创建编辑器
	 * @param array $params 编辑器参数集
	 */
	public function create($params = array())
	{
		return $this->getClient()->create($params);
	}

	/**
     * 获取SDK实例
     */
    public function getClient()
    {
        if($this->client === null) {
            $this->client = new SDK($this->config);
        }
        return $this->client;
    }
}