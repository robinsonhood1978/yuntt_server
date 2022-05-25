<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;
use yii\helpers\ArrayHelper;

use common\library\Language;
use common\library\Arrayfile;

/**
 * @Id Setting.php 2018.9.3 $
 * @author mosir
 */
 
class Setting extends Arrayfile
{
	var $filename = null;
	
	public function __construct()
	{
		$this->filename = Yii::getAlias('@frontend') . '/web/data/setting.php';
	}
	
	public static function getInstance()
	{
		return new Setting();
	}
	
	public function getAll($setting = array())
	{
		$default = self::getDefault();
		$setting = parent::getAll();
		
		return ArrayHelper::merge($default, $setting);
	}
	
	public static function getDefault($setting = array())
	{
		$result = array(
			'time_zone' 		=> '8',
            'price_currency' 		=> '&#36',
            'price_format'  	=> '&yen;%s',
            'site_name' 		=> Language::get('default_site_name'),
            'site_title' 		=> Language::get('default_site_title'),
			'site_keywords'	    => Language::get('default_site_keywords'),
            'site_description'	=> Language::get('default_site_description'),
            'site_status' 		=> '1',
     		'hot_keywords' 		=> Language::get('default_hot_keywords'),
            'captcha_status' 	=> array(),
            'store_allow' 			=> '1',
            'upgrade_required' 		=> '10',
            'default_goods_image' 	=> 'data/system/default_goods_image.jpg',
            'default_store_logo' 	=> 'data/system/default_store_logo.gif',
            'default_user_portrait' => 'data/system/default_user_portrait.gif',
            'site_logo'				=> 'data/system/site_logo.gif',
            'template_name' 		=> 'default',
			'wap_template_name'		=> 'default',
        );
		return $result;		
	}
	
	public static function getTimezone()
	{
		$result = array(
            '-12'	=>	'(GMT -12:00) Eniwetok, Kwajalein',
            '-11'	=>	'(GMT -11:00) Midway Island, Samoa',
            '-10'	=>	'(GMT -10:00) Hawaii',
            '-9'	=>	'(GMT -09:00) Alaska',
            '-8'	=>	'(GMT -08:00) Pacific Time (US &amp; Canada), Tijuana',
            '-7'	=>	'(GMT -07:00) Mountain Time (US &amp; Canada), Arizona',
            '-6'	=>	'(GMT -06:00) Central Time (US &amp; Canada), Mexico City',
            '-5'	=>	'(GMT -05:00) Eastern Time (US &amp; Canada), Bogota, Lima, Quito',
            '-4'	=>	'(GMT -04:00) Atlantic Time (Canada), Caracas, La Paz',
            '-3.5'	=>	'(GMT -03:30) Newfoundland',
            '-3'	=>	'(GMT -03:00) Brassila, Buenos Aires, Georgetown, Falkland Is',
            '-2'	=>	'(GMT -02:00) Mid-Atlantic, Ascension Is., St. Helena',
            '-1'	=>	'(GMT -01:00) Azores, Cape Verde Islands',
            '0'		=>	'(GMT) Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia',
            '1'		=>	'(GMT +01:00) Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
            '2'		=>	'(GMT +02:00) Cairo, Helsinki, Kaliningrad, South Africa',
            '3'		=>	'(GMT +03:00) Baghdad, Riyadh, Moscow, Nairobi',
            '3.5'	=>	'(GMT +03:30) Tehran',
            '4'		=>	'(GMT +04:00) Abu Dhabi, Baku, Muscat, Tbilisi',
            '4.5'	=>	'(GMT +04:30) Kabul',
            '5'		=>	'(GMT +05:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
            '5.5'	=>	'(GMT +05:30) Bombay, Calcutta, Madras, New Delhi',
            '5.75'	=>	'(GMT +05:45) Katmandu',
            '6'		=>	'(GMT +06:00) Almaty, Colombo, Dhaka, Novosibirsk',
            '6.5'	=>	'(GMT +06:30) Rangoon',
            '7'		=>	'(GMT +07:00) Bangkok, Hanoi, Jakarta',
            '8'		=>	'(GMT +08:00) Beijing, Hong Kong, Perth, Singapore, Taipei',
            '9'		=>	'(GMT +09:00) Osaka, Sapporo, Seoul, Tokyo, Yakutsk',
            '9.5'	=>	'(GMT +09:30) Adelaide, Darwin',
            '10'	=>	'(GMT +10:00) Canberra, Guam, Melbourne, Sydney, Vladivostok',
            '11'	=>	'(GMT +11:00) Magadan, New Caledonia, Solomon Islands',
            '12'	=>	'(GMT +12:00) Auckland, Wellington, Fiji, Marshall Island',
        );
		
		return $result;
	}
}