<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;

$config = array(
    'id' => 'dashboard-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'selectableRows' => 2,
            'value' => '$data->id',
            'htmlOptions' => array('style' => 'width:10px;'),
        ),
        array(
            'name' => 'id',
            'value' => '$row+1',
            'htmlOptions' => array( 'style' => 'width:40px;'),
        ),
        array(
            'name' => 'dashboard_title',
            'value' => '$data->dashboard_title',
        	'htmlOptions' => array( 'style' => 'width:120px;'),
        ),
        array(
            'name' => 'dashboard_url',
            'value' => '$data->dashboard_url',
        	'htmlOptions' => array( 'style' => 'width:150px;'),
        ),
    	array(
    		'name' => 'type',
    		'value' => '$data->getMyconfig("type",$data->type)',
    		'htmlOptions' => array( 'style' => 'width:50px;'),
    	),
        array(
            'name' => 'is_global',
            'value' => '$data->getMyconfig("is_global",$data->is_global)',
        	'htmlOptions' => array( 'style' => 'width:80px;'),
        ),
        array(
            'name' => 'status',
            'value' => 'VHelper::getStatusLable($data->status)',
        	'htmlOptions' => array( 'style' => 'width:60px;'),
        )
    ),
    'tableOptions' => array(
        'layoutH' => 135,
    ),
    'pager' => array(),
);

$config['toolBar'] = array(
    array(
        'text' => Yii::t('system', 'Add Dashboard'),
        'url' => '/systems/dashboard/create',
        'htmlOptions' => array(
            'class' => 'add',
            'target' => 'dialog',
            'rel' => 'dashboard-grid',
            'width' => '600',
            'height' => '400',
        )
    ),
    array(
        'text' => Yii::t('system', 'Batch delete the DashBoards'),
        'url' => '/systems/dashboard/delete',
        'htmlOptions' => array(
            'class' => 'delete',
            'title' => Yii::t('system', 'Really want to delete these records?'),
            'target' => 'selectedTodo',
            'rel' => 'dashboard-grid',
            'postType' => 'string',
            'callback' => 'navTabAjaxDone',
        )
    ),
);

$config['columns'][] = array(
    'header' => Yii::t('system', 'Operation'),
    'class' => 'CButtonColumn',
    'headerHtmlOptions' => array('width' => '60', 'align' => 'center'),
    'template' => '{edit}',
    'buttons' => array(
        'edit' => array(
            'url'       => 'Yii::app()->createUrl("/systems/dashboard/update", array("id" => $data->id))',
            'label'     => Yii::t('system', 'Edit the DashBoard'),
            'options'   => array(
                'target'    => 'dialog',
                'class'     =>'btnEdit',
                'rel' 		=> 'dashboard-grid',
                'width'     => '600',
                'height'    => '400',
            ),
        ),

    ),
);
$this->widget('UGridView', $config);
?>


