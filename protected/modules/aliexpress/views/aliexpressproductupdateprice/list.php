<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'aliexpressLogupdateprice-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array(
					'text' => '批量改价',
					'url'  => '/aliexpress/aliexpressproductupdateprice/updateprice',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'aliexpressLogupdateprice-grid',
						'title'	   => '确定要改价吗?',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
						'width'    => ''
					)
			),
	),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:30px;'),
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
					'name' => 'sku',
					'value' => '$data->sku',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:100px;'),
			),

			array(
					'name' => 'product_id',
					'value' => '$data->product_id',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),

			array(
					'name' => 'old_price',
					'value' => '$data->old_price',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
			),

			array(
					'name' => 'update_price',
					'value' => '$data->update_price',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
			),

			array(
					'name' => 'discount',
					'value' => '$data->discount',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
			),

			array(
					'name'  => 'status',
					'value' => 'AliexpressLogUpdatePrice::model()->getStatus($data->status)',
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
					'name' => 'operation_user_id',
					'value' => 'User::model()->getUserNameScalarById($data->operation_user_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:120px;'),
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
					'name'  => 'event',
					'value' => 'AliexpressLogUpdatePrice::model()->getEvent($data->event)',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:180px;',
					),
			),			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>