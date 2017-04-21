<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'aliexpresseditsimpleproductfiledmessages-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
					'htmlOptions' => array('style' => 'width:20px;'),
			),			
			array(
					'name'  => 'product_id',
					'value' => '$data->product_id',
					'htmlOptions' => array('style' => 'width:130px;'),
			),
			array(
					'name'  => 'upload_status',
					'value' => 'AliexpressUpdateFtImageLog::model()->getStatus($data->upload_status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name'  => 'upload_message',
					'value' => '$data->upload_message',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'upload_time',
					'value' => '$data->upload_time',
					'htmlOptions' => array('style' => 'width:90px;'),
			),
			array(
					'name'  => 'update_status',
					'value' => 'AliexpressUpdateFtImageLog::model()->getStatus($data->update_status)',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name'  => 'update_message',
					'value' => '$data->update_message',
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name'  => 'operator',
					'value' => 'User::model()->getUserNameScalarById($data->operator)',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name' => 'operate_time',
					'value' => '$data->operate_time',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name' => 'operate_message',
					'value' => '$data->operate_message',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			)
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);
?>