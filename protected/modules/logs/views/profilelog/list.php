<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
    'id' => 'profilelog-grid',
    'template' => THelper::getListTpl(),
    'dataProvider' => $model->search(null),
    'filter' => $model,
    'columns' => array(
       array(
            'name'=> 'id',
            'value'=>'$row+1',     
        ),       
        'tag', 
        'keywords',
        'message',  
        'request_url',      
        'log_time'
    ),
    'tableOptions' => array(
        'layoutH' => 140,
    ),
    'pager' => array(),
));
?>
