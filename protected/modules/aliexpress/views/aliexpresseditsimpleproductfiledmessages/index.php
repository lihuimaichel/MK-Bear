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
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:90px;'),
			),
			array(
					'name'  => 'field_name',
					'value' => 'AliexpressEditSimpleProductFiledMessages::model()->getFieldName($data->field_name)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name'  => 'aliexpress_product_id',
					'value' => '$data->aliexpress_product_id',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'sku',
					'value' => '$data->sku',
					'htmlOptions' => array('style' => 'width:90px;'),
			),
			array(
					'name'  => 'send_msg',
					'value' => '$data->send_msg',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name'  => 'status',
					'value' => 'AliexpressEditSimpleProductFiledMessages::model()->getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name'  => 'create_user_id',
					'value' => 'User::model()->getUserNameScalarById($data->create_user_id)',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name' => 'create_time',
					'value' => '$data->create_time',
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