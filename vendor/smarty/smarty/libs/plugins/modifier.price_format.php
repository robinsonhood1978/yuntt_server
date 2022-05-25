<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty price_format modifier plugin
 * Type:     modifier<br>
 * Name:     price_format<br>
 * Input:<br>
 *          - string: input price number
 *          - format: sprintf format for output
 *          - default: default price if $number is empty
 *
 * @author  shopwind <shopwind.net>
 * @version 1.0
 */
function smarty_modifier_price_format($number, $format = null, $default = '0.00', $formatter = 'auto')
{
	if (empty($number)) $number = $default;
    $price = number_format($number, 2);

    if ($format === NULL)
    {
        $format = \Yii::$app->params['price_format'];
    }
	
    return sprintf($format, $price);
}
