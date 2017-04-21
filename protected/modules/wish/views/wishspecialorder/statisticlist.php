<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishorderstatistics-grid',
	'dataProvider' => $model->search(null),
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
								'style' => 'width:60px;'
									)
				),
				
				array(
						'name' => 'buyer_id',
						'value' => '$data->buyer_id',
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
				
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:150px;',
						),
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/wish/wishspecialorder/showbuyerid", array("buyer_id" => $data->buyer_id))',
										'label'     => Yii::t('wish_order', 'Statistics BuyerId Order'),
										'options'   => array(
												'target'    => 'navTab',
												'class'     => 'btnInfo',
												'rel' 		=> 'wishorderstatistics-grid',
												'postType' 	=> '',
												'callback' 	=> '',
												'height' 	=> '480',
												'width' 	=> '650'
										),
								),
				
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