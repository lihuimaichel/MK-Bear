<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishorderbuyerstatistics-grid',
	'dataProvider' => $model->search($buyerId),
	'filter' => $model,
	'toolBar' => array(
			/* array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/wish/wishspecialordershipcode/add',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'wishordershipcodeadd-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			) */
	),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'	=> '$row+1',
						'htmlOptions'=>array(
								'style' => 'width:20px;'
									)
				),
				
				array(
						'name' => 'buyer_id',
						'value' => '$data->buyer_id',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'paydate',
						'value' => '$data->paydate',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
             	array(
						'name'  => 'order_count',
						'value' => '$data->order_count',
             			
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:160px;',
						),
				),
				

				array(
						'name' => 'sku_count',
						'value'=> '$data->sku_count',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:80px;'),
				),
			
				array(
						'name'  => 'price_count',
						'value' => '$data->price_count',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
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