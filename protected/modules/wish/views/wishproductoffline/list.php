<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishproductoffline-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
					'htmlOptions'=>array(
							'style' => 'width:60px;'
								)
			),

			array(
					'name' => 'product_id',
					'value' => '$data->product_id',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),

			array(
					'name' => 'sku',
					'value' => '$data->sku',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),

			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:120px;',
					),
			),

			array(
					'name'  => 'status',
					'value' => 'WishOffline::model()->getStatus($data->status)',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:80px;',
					),
			),

			array(
					'name'  => 'message',
					'value' => '$data->message',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:260px;',
					),
			),

			array(
					'name'  => 'start_time',
					'value' => '$data->start_time',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
			),
			
			array(
					'name' => 'operation_user_id',
					'value' => 'User::model()->getUserNameScalarById($data->operation_user_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:120px;'),
			),			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>