<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishproductadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'	=> '$data->order_id',
						'htmlOptions'=>array(
										
									)
				),
				
				array(
						'name' => 'order_id',
						'value' => '$data->order_id',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:120px;'),
				),
	
             	array(
						'name'  => 'sku',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:100px;',
						),
				),
				array(
						'name' => 'account_name',
						'value' => '$data->account_name',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),			
				
				array(
						'name' => 'platform_order_id',
						'value'=> '$data->platform_order_id',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name'  => 'ship_info',
						'value' => '$data->ship_info',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:100px;',
						),
				),
				array(
						'name'  => 'ship_status',
						'value' => '$data->ship_status_text',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'complete_status',
						'value' => '$data->complete_status_text',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'total_price',
						'value' => '$data->total_price',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				
				array(
						'name'  => 'paytime',
						'value' => '$data->paytime',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:left;width:100px;',
						),
				),
			
    		   array(
    		        'name'  => 'ori_pay_time',
    		        'value' => '$data->ori_pay_time',
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;width:100px;',
    		        ),
    		    ),
				array(
						'name' => 	'timestamp',
						'type' => 	'raw',
						'value'=>	'$data->timestamp',
						'htmlOptions' => array('style' => 'width:100px;'),
				),
				array(
						'name'  => 'buyer_id',
						'type'  => 'raw',
						'value' => '$data->buyer_id',
						'htmlOptions' => array('style' => 'width:160px;'),
				),
				array(
						'name'  => 'ship_country',
						'type'  => 'raw',
						'value' => '$data->ship_country_name',
						'htmlOptions' => array('style' => 'width:80px;'),
				),
				array(
						'name'  => 'reciver_address',
						'type'  => 'raw',
						'value' => '$data->reciver_address',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>