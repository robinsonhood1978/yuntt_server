<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty url_format modifier plugin
 * Type:     modifier<br>
 * Name:     url_format<br>
 *
 * @author  shopwind <shopwind.net>
 * @version 1.0
 */

use common\library\Page;

/**
 * 将相对地址修改为绝对地址，以适应不同的应用显示
 * @desc 主要是处理图片路径，不要使用在JS文件路径（以免引起跨域问题）
 */
function smarty_modifier_url_format($url)
{
	return Page::urlFormat($url);
}