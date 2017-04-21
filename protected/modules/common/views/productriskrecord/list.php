<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'aliexpressproductadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
		),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions' => array('style' => 'width:50px;'),
				),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),
				array(
						'name' => 'product_id',
						'value'=> 'VHelper::getBoldShow($data->subject)',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name' => 'platform_code',
						'value'=> 'Platform::getPlatformList($data->platform_code)',
						'htmlOptions' => array('style' => 'width:80px;'),
				),
				
    		    array(
	    		        'name'  => 'status',
	    		        'value' => 'VHelper::getRunningStatusLable($data->status, $data->status_desc)',
	    		        'type'  => 'raw',
	    		        'htmlOptions' => array( 'style' => 'text-align:center;','style' => 'width:150px;' ),
    		    ),
				array(
						'name' => 'modify_time',
						'value'=>'$data->modify_time',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name'  => 'modify_user_id',
						'value' => 'MHelper::getUsername($data->modify_user_id)',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name'  => 'note',
						'value' => 'VHelper::getBoldShow($data->note)',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:300px;'),
				),
				/* array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/lazada/lazadaproductadd/update", array("id" => $data->id))',
										'label'     => Yii::t('lazada', 'Edit Lazada Product'),
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '500',
												'height'    => '300',
										),
								),
				
						),
				), */
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>