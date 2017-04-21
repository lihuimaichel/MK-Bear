<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
    'id' => 'wishaccountrelations-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'toolBar' => array(
        array(
            'text'          => Yii::t('system', 'Add New Account'),
            'url'           => Yii::app()->createUrl('/wish/wishaccountrelations/add'),
            'htmlOptions'   => array(
                'class'     => 'add',
                'target'    => 'dialog',
                'rel'       => 'wishaccountrelations-grid',
                'postType'  => '',
                'callback'  => '',
                'height'   => '500',
                'width'    => '580'
            )
        ),

        array(
            'text' => Yii::t('system', 'Delete'),
            'url'  => Yii::app()->createUrl('/wish/wishaccountrelations/delete'),
            'htmlOptions' => array (
                'class'    => 'delete',
                'target'   => 'selectedToDo',
                'rel'      => 'wishaccountrelations-grid',
                'title'	   => Yii::t('wish', 'Confirm to delete?'),
                'postType' => 'string',
                'callback' => 'dialogAjaxDone',
            )
        ),
    ),
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'selectableRows' =>2,
            'value'=> '$data->id',
            'htmlOptions' => array('style' => 'width:30px;'),
        ),
        array(
            'name' => 'account_name',
            'value' => '$data->account_id',
            'htmlOptions' => array('style' => 'width:120px;'),
        ),
        array(
            'name' => 'department_name',
            'value' => '$data->department_id',
            'htmlOptions' => array('style' => 'width:120px;'),
        ),
        array(
            'name' => 'seller_name',
            'value' => '$data->seller_id',
            'htmlOptions' => array('style' => 'width:120px;'),
        ),

        array(
            'name' => 'created_at',
            'value' => '$data->created_at',
            'htmlOptions' => array('style' => 'width:150px;'),
        ),
        array(
            'name' => 'expired_at',
            'value' => '$data->expired_at',
            'htmlOptions' => array('style' => 'width:150px;'),
        ),

               array(
                    'name' => 'status',
                    'value' => '$data->status',
                    'htmlOptions' => array('style' => 'width:120px;'),
                ),
    ),
    'tableOptions' 	=> array(
        'layoutH' 	=> 90,
    ),
    'pager' 		=> array(),
));
?>

<style type="text/css">
    .pageFormContent dl.nowrap dd, .nowrap dd{width: 400px !important;}
</style>

