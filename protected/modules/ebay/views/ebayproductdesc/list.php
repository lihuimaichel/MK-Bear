<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_desc_list',
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
							'name'=>'language_code',
							'value' => '$data->language_code',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'template_name',
							'value' => '$data->template_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:300px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'title_prefix',
							'value' => '$data->title_prefix',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:300px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'title_suffix',
							'value' => '$data->title_suffix',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:300px;',
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
							'name'=>'opreator',
							'value' => '$data->opreator',
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
					'url' => '/ebay/ebayproductdesc/batchdelete',
					'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
								'target' => 'selectedTodo',
								'rel' => 'ebay_product_desc_list',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
					) 
			),
			
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/ebay/ebayproductdesc/addtemplate',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'ebay_product_desc_list',
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