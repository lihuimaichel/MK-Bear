<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'priceminister_product_add_list',
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
							'name'=>'sku',
							'value' => '$data->sku',
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
									'style' => 'width:50px;',
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'type_id',
							'value' => '$data->type_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:200px;',
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					
					array(
							'name'=>'title',
							'value' => '$data->title',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:260px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					/*array(
							'name'=>'category_id',
							'value' => '$data->category_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:260px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),*/
					/*array(
							'name'=>'price',
							'value' => '$data->price',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:160px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),*/
			
					array(
							'name'=>'status_text',
							'value' => '$data->status_text',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:200px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}&nbsp;&nbsp;&nbsp;{update1}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:100px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/priceminister/priceministerproductadd/update", array("add_id" => $data->id))',
									'label'     => Yii::t('ebay', 'Edit Public Add Info'),
									'options'   => array(
											'target'    => 'navTab',
											'rel' 		=> 'priceminister_product_add_list',
											'class'     =>	'btnEdit',
											'postType' => '',
											'callback' => '',
									),
									'visible'	=>	'($data->status == 0 || $data->status==3)'
							),
								
							'update1' => array(
									'url'       => 'Yii::app()->createUrl("/priceminister/priceministerproductadd/uploadproduct", array("add_id" => $data->id))',
									'label'     => Yii::t('aliexpress', 'Upload Now'),
									'options'   => array(
											'title'     => Yii::t('aliexpress', 'Are you sure to upload these'),
											'target'    => 'ajaxTodo',
											'rel'       => 'priceminister_product_add_list',
											'postType'  => 'string',
											'callback'  => 'navTabAjaxDone',
											'onclick'	=>	'',
											'style'		=>	'width:80px;height:28px;line-height:28px;'
									),
									'visible'	=>	'$data->visiupload'
							),
					),
			),
					array(
							'name'=>'listing_type',
							'value' => '$data->listing_type',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			
					array(
							'name'=>'update_user_id',
							'value' => 'MHelper::getUsername($data->update_user_id)',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
			
					array(
							'name'=>'create_time',
							'value' => '$data->create_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
			
					array(
							'name'=>'last_response_time',
							'value' => '$data->last_response_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'import_id',
							'value' => '$data->import_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'product_id',
							'value' => '$data->product_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					
				),
	'toolBar'	=>	array(
		
			array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => Yii::app()->createUrl('/priceminister/priceministerproductadd/batchdel'),
					'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
								'target' => 'selectedTodo',
								'rel' => 'priceminister_product_add_list',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
					) 
			),
			
	),
	'pager' => array(),
	'tableOptions' => array(
			'layoutH' => 150,
			'tableFormOptions' => true,
	),
));

?>