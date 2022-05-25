<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {res} function plugin
 *
 * @author  shopwind <shopwind.net>
 * @version 1.0
 *
 * @param array $params parameters
 * @param Smarty_Internal_Template $template template object
 *
 * @throws SmartyException
 * @return string
 */

use common\library\Resource;

/**
 * 除非有特殊指定，要不一律建议返回相对路径，可以解决多个域名访问的资源路径问题
 */
function smarty_function_res($params, $template)
{
	return Resource::getThemeAssetsUrl($params);
}
