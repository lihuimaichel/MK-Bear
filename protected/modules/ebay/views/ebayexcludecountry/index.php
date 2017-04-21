<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_exclude_shipping_country_list',
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
							'name'=>'account_name',
							'value' => '$data->account_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'country_code',
							'value' => '$data->country_code',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:500px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'country_name',
							'value' => '$data->country_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:600px;',
										
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
											'url'       => 'Yii::app()->createUrl("/ebay/ebayexcludecountry/update", array("site_id" => $data->site_id, "account_id"=>$data->account_id, "id"=>$data->id))',
											'label'     => '修改',
											'options'   => array(
													'target'    => 'dialog',
													'class'     =>	'btnEdit',
													'height' 	=> '580',
													'width' 	=> '950' ,
											),
									),
							),
					),
				),
	'toolBar'	=>	array(
		
			/* array(
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
			), */
			
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/ebay/ebayexcludecountry/create',
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