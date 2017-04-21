<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishlistingholdedoffline-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(

			array (
					'text' => Yii::t ( 'system', '批量处理' ),
					'url' => '/wish/wishlistingholdedoffline/batchupdate',
					'htmlOptions' => array (
							'class' => 'delete',
							'target' => 'selectedToDo',
							'rel' => 'wishlistingholdedoffline-grid',
							'title'	=>	'确定执行已处理操作？',
							'postType' => 'string',
							'callback' => '',
							'height' => '',
							'width' => ''
					)
			),
					
	),
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
						'name'  => 'account_id',
						'value' => '$data->account_name',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),				
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),			
				array(
						'name' => 'product_id',
						'value' => '$data->product_id',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'total',
						'value' => '$data->total',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'type',
						'value' => '$data->type',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'status',
						'value' => '$data->status',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'create_time',
						'value' => '$data->create_time',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),	
				array(
						'name' => 'update_time',
						'value' => '$data->update_time',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),															
				array(
						'name' => 'times',
						'value' => '$data->times',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),

				// array(
				// 		'name' => 'seller_id',
				// 		'value' => 'User::model()->getUserNameScalarById($data->seller_id)',
				// 		'type'  => 'raw',
				// 		'htmlOptions' => array('style' => 'width:80px;'),
				// ),
			
/*	
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:150px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/wish/wishlistingholdedoffline/update", array("id" => $data->id))',
									'label'     => Yii::t('wish_listing', '修改'),
									'options'   => array(
											'target'    => 'dialog',
											'class'     => 'btnEdit',
											'rel' 		=> 'wishlistingholdedoffline-grid',
											'postType' 	=> '',
											'callback' 	=> '',
											'height' 	=> '',
											'width' 	=> ''
									),
							),
					),
			),
		*/	
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);
$this->widget('UGridView', $options);
?>
