<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_sale_price_config_list',
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
							'name'=>'start',
							'value' => '$data->start',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'end',
							'value' => '$data->end',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'standard_rate',
							'value' => '$data->standard_rate',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'min_rate',
							'value' => '$data->min_rate',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'float_rate',
							'value' => '$data->float_rate',
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
										'url'       => 'Yii::app()->createUrl("/ebay/ebaysalepriceconfig/update", array("id" => $data->id))',
										'label'     => Yii::t('wish_listing', 'Edit Publish Info'),
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
	'toolBar'	=>	array(
                        array(
                                    'text' => Yii::t('system', 'Add'),
                                    'url' => '/ebay/ebaysalepriceconfig/add',
                                    'htmlOptions' => array(
                                            'class' 	=> 'add',
                                            'target' 	=> 'dialog',
                                            'mask'  	=> true,
                                            'width' 	=> '500',
                                            'height' 	=> '400',
                                            'onclick'   => false
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