<?php

$abcdefile = Yii::getAlias('@frontend') . '/web/data/config.php';
return [
    'components' => array_merge(file_exists($abcdefile) ? require($abcdefile) : [], [])
];