<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'aliexpressaccountseller-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
			),			
			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:90px;'),
			),
			array(
					'name'  => 'buyer_login_id',
					'value' => '$data->buyer_login_id',
					'htmlOptions' => array('style' => 'width:140px;'),
			),
			array(
					'name'  => 'platform_order_id',
					'value' => '$data->platform_order_id',
					'htmlOptions' => array('style' => 'width:190px;'),
			),
			array(
					'name'  => 'gmt_create',
					'value' => '$data->gmt_create',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name'  => 'pay_amount',
					'value' => '$data->pay_amount',
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name'  => 'receipt_country',
					'value' => '$data->receipt_country',
					'htmlOptions' => array('style' => 'width:80px;'),
			),
			array(
					'name'  => 'send_msg',
					'value' => '$data->send_msg',
					'htmlOptions' => array('style' => 'width:180px;'),
			),
			array(
					'name'  => 'status',
					'value' => 'AliexpressNonPaymentOrderSendMessages::model()->getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:80px;'),
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