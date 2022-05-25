<?php
return array (
  'version' => '1.0',
  'subject' => '{$site_name}提醒:您的店铺【{$store.store_name}】已被关闭',
  'content' => '<p>尊敬的{$store.owner_name}:</p>
<p style="padding-left: 30px;">您的店铺【{$store.store_name}】因为【{$reason}】被平台关闭，如有疑问，请联系客服。</p>
<p style="text-align: right;">{$site_name}</p>
<p style="text-align: right;">{$send_time}</p>',
);