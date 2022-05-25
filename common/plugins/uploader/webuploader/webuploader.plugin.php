<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\uploader\webuploader;

use yii;

use common\plugins\BaseUploader;
use common\plugins\uploader\webuploader\SDK;

/**
 * @Id Webuploader.plugin.php 2018.9.5 $
 * @author mosir
 */

class Webuploader extends BaseUploader
{
	/**
     * 插件实例
	 * @var string $code
	 */
	protected $code = 'webuploader';

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
     * 创建上传组件组件
	 * @param array $params 上传组件参数集
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