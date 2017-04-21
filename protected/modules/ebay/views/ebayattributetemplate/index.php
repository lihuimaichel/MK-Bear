<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_attribute_template_list',
	'filter'	=>	$model,
	'dataProvider'	=>	$model->search(),
	'selectableRows'	=>	2,
	'columns'	=>	array(
					array(
						'class'=>'CCheckBoxColumn',
						'value'=>'$data->id',
						'selectableRows' => 2,
						'htmlOptions' => array(
								'style' => 'width:20px;',
						
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
								'style' => 'width:20px;',
								'onclick'=>''
						),
						'checkBoxHtmlOptions' => array(
								'onchange' => '',
								'onpropertychange' => '',
								'oninput' => '',
						),
							
					),
					
					array(
							'name'=>'name',
							'value' => '$data->name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'config_type_name',
							'value' => '$data->config_type_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'site_name',
							'value' => '$data->site_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'abroad_warehouse',
							'value' => '$data->abroad_warehouse_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'country',
							'value' => '$data->country',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					
					array(
							'name'=>'location',
							'value' => '$data->location',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			
					array(
							'name'=>'dispatch_time_max',
							'value' => '$data->dispatch_time_max',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					
					array(
							'name'=>'listing_duration',
							'value' => '$data->listing_duration',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			
					array(
							'name'=>'opration_date',
							'value' => '$data->opration_date',
							'type'=>'raw',
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
							'template' => '{edit}&nbsp;&nbsp;',
							'htmlOptions' => array(
									'style' => 'text-align:center;width:60px;',
							),
							'buttons' => array(
									'edit' => array(
											'url'       => 'Yii::app()->createUrl("/ebay/ebayattributetemplate/update", array("id" => $data->id))',
											'label'     => '修改ebay站点参数',
											'options'   => array(
													'target'    => 	'dialog',
													'rel' 		=> 'ebay_product_attribute_template_list',
													'class'     =>	'btnEdit',
													'height' 	=> '680',
													'width' 	=> '850' ,
													'postType' => '',
													'callback' => '',
											),
									),
							),
					),
				),
	'toolBar'	=>	array(
		
			array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => '/ebay/ebayattributetemplate/batchdelete',
					'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
								'target' => 'selectedTodo',
								'rel' => 'ebay_product_attribute_template_list',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
					) 
			),
			
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/ebay/ebayattributetemplate/choosesite',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'ebay_product_attribute_template_list',
							'postType' => '',
							'callback' => '',
							'height' => '680',
							'width' => '850' 
					) 
			) 
                        
	),
	'pager' => array(),
	'tableOptions' => array(
			'layoutH' => 150,
			'tableFormOptions' => true,
	),
));


?>