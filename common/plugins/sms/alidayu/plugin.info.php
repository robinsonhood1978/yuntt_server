<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\sms\alidayu;

use yii;
use yii\helpers\Url;

use common\library\Language;

/**
 * @Id plugin.info.php 2018.8.3 $
 * @author mosir
 */
return array(
    'code' => 'alidayu',
    'name' => '阿里云通信',
    'desc' => '阿里巴巴云通信（原阿里大鱼短信平台）',
    'author' => 'SHOPWIND',
	'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'buttons' => array(
        array(
            'label' => Language::get('manage'),
            'url' => Url::toRoute(['msg/index'])
        )
    ),
    'config' => array(
		'AppKey' => array(
            'type' => 'text',
            'text' => 'AppKey'
        ),
		'AppScrect' => array(
            'type' => 'text',
            'text' => 'AppScrect'
        ),
        /*'registerVerifyId' => array(
            'type' => 'text',
            'text' => '注册验证类短信模板ID',
        ),
        'registerVerifyBody' => array(
            'type' => 'text',
            'text' => '注册验证类短信模板内容',
        ),
        'findPasswordVerifyId' => array(
            'type' => 'text',
            'text' => '找回密码验证类短信模板ID',
        ),
        'findPasswordVerifyBody' => array(
            'type' => 'text',
            'text' => '找回密码验证类短信模板内容',
        ),
        'newOrderNotifyId' => array(
            'type' => 'text',
            'text' => '买家下单提醒卖家通知类模板ID',
        ),
        'newOrderNotifyBody' => array(
            'type' => 'text',
            'text' => '买家下单提醒卖家通知类模板内容',
        ),
        'payOrderNotifyId' => array(
            'type' => 'text',
            'text' => '买家付款提醒卖家通知类模板ID',
        ),
        'payOrderNotifyBody' => array(
            'type' => 'text',
            'text' => '买家付款提醒卖家通知类模板内容',
        ),
        'shippedOrderNotifyId' => array(
            'type' => 'text',
            'text' => '卖家发货提醒买家通知类模板ID',
        ),
        'shippedOrderNotifyBody' => array(
            'type' => 'text',
            'text' => '卖家发货提醒买家通知类模板内容',
        ),
        'confirmOrderNotifyId' => array(
            'type' => 'text',
            'text' => '买家收货提醒卖家通知类模板ID',
        ),
        'confirmdOrderNotifyBody' => array(
            'type' => 'text',
            'text' => '买家收货提醒卖家通知类模板内容',
        ),
        'refundOrderNotifyId' => array(
            'type' => 'text',
            'text' => '买家退款提醒卖家通知类模板ID',
        ),
        'refundApplyNotifyBody' => array(
            'type' => 'text',
            'text' => '买家退款提醒卖家通知类模板内容',
        ),
        'refundAgreeNotifyId' => array(
            'type' => 'text',
            'text' => '退款成功提醒买家通知类模板ID',
        ),
        'refundAgreeNotifyBody' => array(
            'type' => 'text',
            'text' => '退款成功提醒买家通知类模板内容',
        ),*/
        'scene' => array(
            'name' => 'config[scene]',
            'type' => 'checkbox',
            'text' => '启用场景',
            'items' => array(
                'register' => '用户注册',
                'find_password' => '找回密码'
            )
        )
    )
);