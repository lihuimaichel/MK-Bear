<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_auction_list',
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
							'name'=>'start_time',
							'value' => '$data->start_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					),
			
					array(
							'name'=>'plan_day',
							'value' => '$data->plan_day',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					),
			
					array(
							'name'=>'auction_status_text',
							'value' => '$data->auction_status_text',
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
								'text' => Yii::t('system', '开启循环刊登'),
								'url' => '/ebay/ebayproductauction/updatestatus/type/2',
								'htmlOptions' => array(
										'class' => 'delete',
										'title' => Yii::t ( 'system', 'Really want to open these records?' ),
										'target' => 'selectedTodo',
										'rel' => 'ebay_product_auction_list',
										'postType' => 'string',
										'warn' => Yii::t ( 'system', 'Please Select' ),
										'callback' => 'navTabAjaxDone'
								)
						),
                        array(
                                    'text' => Yii::t('system', '取消循环刊登'),
                                    'url' => '/ebay/ebayproductauction/updatestatus/type/1',
                                    'htmlOptions' => array(
                                           	'class' => 'delete',
											'title' => Yii::t ( 'system', 'Really want to cancel these records?' ),
											'target' => 'selectedTodo',
											'rel' => 'ebay_product_auction_list',
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

<script type="text/javascript">

</script>