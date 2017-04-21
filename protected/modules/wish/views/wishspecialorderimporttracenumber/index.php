<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishorderimportshipcode-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/wish/wishspecialorderimporttracenumber/add',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'wishorderimportshipcode-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
			array (
					'text' => Yii::t ( 'system', 'Delete' ),
					'url' => '/wish/wishspecialorderimporttracenumber/batchdel',
					'htmlOptions' => array (
							'title'     => Yii::t('wish_listing', 'Are you sure to delete these?! Note:Only delete not upload success'),
							'class' => 'delete',
							'target' => 'selectedTodo',
							'rel' => 'wishorderimportshipcode-grid',
							'postType' => 'string',
							'callback' => 'navTabAjaxDone',
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
						'name' => 'trace_number',
						'value' => '$data->trace_number',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'ship_code',
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
						'name'  => 'ship_country_name',
						'value' => '$data->ship_country_name',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),
				
				array(
						'name'  => 'ship_date',
						'value' => '$data->ship_date',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),
	
			/* array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:150px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/wish/wishspecialorderimporttracenumber/update", array("id" => $data->id))',
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
			), */
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>