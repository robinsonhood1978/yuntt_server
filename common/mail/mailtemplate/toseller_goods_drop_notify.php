<?php
return array (
  'version' => '1.0',
  'subject' => '{$site_name}提醒:您的商品【{$goods.goods_name}】已被删除',
  'content' => '<p>尊敬的{$store.store_name}:</p>
<p style="padding-left: 30px;">您的商品【{$goods.goods_name}】因为【{$reason}】被平台删除，如有疑问，请联系客服。</p>
<p style="padding-left: 30px;">查看您目前在售的商品请点击以下链接</p>
<p style="padding-left: 30px;"><a href=\'{url route="my_goods/index" baseUrl="{$baseUrl}"}\'>{url route="my_goods/index" baseUrl="{$baseUrl}"}</a></p>
<p style="text-align: right;">{$site_name}</p>
<p style="text-align: right;">{$send_time}</p>',
);