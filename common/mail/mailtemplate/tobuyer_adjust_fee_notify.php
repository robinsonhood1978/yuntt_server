<?php
return array (
  'version' => '1.0',
  'subject' => '{$site_name}提醒:店铺{$order.seller_name}调整了您的订单费用',
  'content' => '<p>尊敬的{$order.buyer_name}:</p>
<p style="padding-left: 30px;">与您交易的店铺{$order.seller_name}调整了您订单号为{$order.order_sn}的订单的费用，请您及时付款。</p>
<p style="padding-left: 30px;">查看订单详细信息请点击以下链接</p>
<p style="padding-left: 30px;"><a href="{url route=\'buyer_order/view\' order_id=$order.order_id}">{url route=\'buyer_order/view\' order_id=$order.order_id}</a></p>
<p style="text-align: right;">{$site_name}</p>
<p style="text-align: right;">{$send_time}</p>',
);