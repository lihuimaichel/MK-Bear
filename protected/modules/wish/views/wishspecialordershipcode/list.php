<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishordershipcodeadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(
			array (
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
			)
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
						'name' => 'ship_name',
						'value' => '$data->ship_name',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
	
             	array(
						'name'  => 'ship_code',
						'value' => '$data->ship_code',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:160px;',
						),
				),
				

				array(
						'name' => 'status',
						'value'=> '$data->status_text',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:80px;'),
				),
			
				array(
						'name'  => 'update_id',
						'value' => '$data->update_username',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),
				array(
						'name'  => 'create_id',
						'value' => '$data->create_username',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),
				
				array(
						'name'  => 'create_time',
						'value' => '$data->create_time',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:left;width:100px;',
						),
				),
			
    		   array(
    		        'name'  => 'update_time',
    		        'value' => '$data->update_time',
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;width:100px;',
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
									'url'       => 'Yii::app()->createUrl("/wish/wishspecialordershipcode/update", array("id" => $data->id))',
									'label'     => Yii::t('wish_listing', 'Edit Publish Info'),
									'options'   => array(
											'target'    => 'dialog',
											'class'     => 'btnEdit',
											'rel' 		=> 'wishordershipcodeadd-grid',
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