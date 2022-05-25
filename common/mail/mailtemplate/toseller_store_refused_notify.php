<?php
return array (
  'version' => '1.0',
  'subject' => '{$site_name}提醒:您的店铺【{$store.store_name}】未通过审核',
  'content' => '<p>尊敬的{$store.owner_name}:</p>
<p style="padding-left: 30px;">抱歉，您的店铺【{$store.store_name}】未通过平台审核，原因为：{$store.apply_remark}。如有疑问，请联系客服。</p>
<p style="text-align: right;">{$site_name}</p>
<p style="text-align: right;">{$send_time}</p>',
);