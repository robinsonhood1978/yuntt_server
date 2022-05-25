<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {widgets} function plugin
 * Type:     function<br>
 * Name:     widgets<br>
 * Params:
 * <pre>
 * - page     - (required)
 * - area     - (optional)
 * </pre>
 *
 * @author  shopwind <shopwind.net>
 * @version 1.0
 *
 * @param array                    $params   parameters
 * @param Smarty_Internal_Template $template template object
 *
 * @throws SmartyException
 * @return string
 */

use common\library\Basewind;
use common\library\Widget;

function smarty_function_widgets($params, $template)
{
	$client = Basewind::getCurrentApp();
	Widget::getInstance($client)->displayWidgets($params);
}