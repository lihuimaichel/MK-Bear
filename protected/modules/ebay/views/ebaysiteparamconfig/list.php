<?php
$row = 0;
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'ebay_site_param_config_list',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array (
			/*array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => '/ebay/ebaysiteparamconfig/batchdelete',
					'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
								'target' => 'selectedTodo',
								'rel' => 'ebay_site_param_config_list',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
					) 
			),*/
			
			array (
					'text' => Yii::t('system', 'Add'),
                    'url' => '/ebay/ebaysiteparamconfig/add',
                    'htmlOptions' => array(
                            'class' 	=> 'add',
                            'target' 	=> 'dialog',
                            'mask'  	=> true,
                            'rel' => 'ebay_site_param_config_list',
                            'width' 	=> '500',
                            'height' 	=> '400',
                            'callback' => 'navTabAjaxDone',
                            'onclick'   => false
                    )
			) 
		),
		'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
						'htmlOptions' => array(
								'style' => 'width:20px;',
						
						),
				),
				array(
						'name'=> 'id',
						'value'=>'$data->id',
						'htmlOptions' => array(
								'style' => 'width:40px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'name' => 'config_name',
						'value'=> '$data->config_name',
						'htmlOptions' => array(
								'style' => 'width:120px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'name' => 'create_time',
						'value'=> '$data->create_time',
						'htmlOptions' => array(
								'style' => 'width:160px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'name' => 'create_user_id',
						'value' => 'MHelper::getUsername($data->create_user_id)',
						'htmlOptions' => array(
								'style' => 'width:100px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'name' => 'update_time',
						'value'=> '$data->update_time',
						'htmlOptions' => array(
								'style' => 'width:160px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'name' => 'update_user_id',
						'value' => 'MHelper::getUsername($data->update_user_id)',
						'htmlOptions' => array(
								'style' => 'width:100px;',
						
						),
						'headerHtmlOptions' => array(
									'align' => 'center',
						),
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:60px;',
						
						),
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/ebay/ebaysiteparamconfig/update", array("id" => $data->id))',
										'label'     => '修改',
										'options'   => array(
												'target'    => 	'dialog',
												'class'     =>	'btnEdit',
												'width' 	=> 	'500',
                                            	'height' 	=> 	'400',
												'onclick'   => 	false
										),
								),
				
						),
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));
?>