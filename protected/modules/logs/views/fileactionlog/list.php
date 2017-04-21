<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
    'id' => 'fileactionlog-grid',
    'template' => THelper::getListTpl(),
    'dataProvider' => $model->search(null),
    'filter' => $model,
	'toolBar' => array(
				    array(
						'text'          => Yii::t('system', 'Batch delete messages'),
						'url'           => '/logs/fileactionlog/delete',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to delete these records?'),
								'target'    => 'selectedTodo',
								'rel'       => 'fileactionlog-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				    ),
	),
    'columns' => array(
       array(
    		'class' => 'CCheckBoxColumn',
    		'selectableRows' => 2,
    		'value' => $model->id,
       ),
       array(
            'name'=> 'id',
            'value'=>'$row+1',     
        ),       
    	array(
    		'name'	=> 'file_type',
    		'value' => 'UebModel::model("DownloadFile")->getFileType($data->file_type)',
    	),
    	'file_name',
    	'file_path',
    	array(
    		'name'	=> 'action_type',
    		'value' =>'UebModel::model("FileActionLog")->getActionType($data->action_type)',
    	),
    	array(
	   		'name'	=> 'create_user_id',
	   		'value' =>'MHelper::getUsername($data->create_user_id)',
        ),
    	'create_time',
    ),		
    'tableOptions' => array(
        'layoutH' => 140,
    ),
    'pager' => array(),
		
));

?>
