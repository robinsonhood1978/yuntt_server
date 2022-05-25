<?php 
return array (
  'version' => '1.0',
  'subject' => '举报处理结果',
  'content' => '<p>尊敬的{$report.username}，</p>
<p>&nbsp;&nbsp;&nbsp; 您于{$report.add_time|date_format:"%Y.%m.%d"}日举报的商品【{$report.goods_name}】已被管理员处理，处理结果为【{$report.content}】。</p>',
);