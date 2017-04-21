<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'aliexpressoverseaswarehouse-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text' => Yii::t('wish_product_statistic', 'Import Excel'),
						'url' => Yii::app()->createUrl('/aliexpress/aliexpressoverseaswarehouse/touploadfile'),
						'htmlOptions' 	=> array(
								'class' 	=> 'add',
								'target' 	=> 'dialog',
								'mask'		=> true,
								'rel' 		=> 'aliexpressoverseaswarehouse-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),				
				array(
						'text' => Yii::t('wish_product_statistic', 'Download Temple'),
						'url' => Yii::app()->createUrl('/aliexpress/aliexpressoverseaswarehouse/downloadtp'),
						'htmlOptions' 	=> array(
								'class' 	=> 'icon',
								'target' 	=> '_blank',
								'mask'		=> true,
								'rel' 		=> 'aliexpressoverseaswarehouse-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),
				array(
						'text' => Yii::t('wish_product_statistic', 'Delete Data'),
						'url' => Yii::app()->createUrl('/aliexpress/aliexpressoverseaswarehouse/delete'),
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('wish_product_statistic', 'Really want to delete the data'),
								'target'    => 'selectedTodo',
								'rel'       => 'aliexpressoverseaswarehouse-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text' => Yii::t('wish_product_statistic', 'Batch Export'),
						'url' => Yii::app()->createUrl('/aliexpress/aliexpressoverseaswarehouse/outfile?'.$request),
						'htmlOptions' 	=> array(
								'title' => '按查询条件批量导出CSV文件',
								'class' 	=> 'edit',
								'target' 	=> '_blank',
								'mask'		=> true,
								'rel' 		=> 'aliexpressoverseaswarehouse-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),												
		),		
		
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:10px;'),
			),		
			array(
					'name' => 'id',
					'value' => '$data->id',
					'htmlOptions' => array('style' => 'width:50px;'),
			),
			array(
					'name' => 'sku',
					'value' => '$data->sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'product_id',
					'value' => '$data->product_id',
					'htmlOptions' => array('style' => 'width:200px;'),
			),									
			array(
					'name' => 'overseas_warehouse_id',
					'value' => '$data->send_warehouse',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name'  => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array(
							'style' => 'width:150px;',
					),
			),				
			array(
					'name' => 'seller',
					'value' => 'User::model()->getUserNameScalarById($data->seller_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:80px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/aliexpress/aliexpressoverseaswarehouse/update", array("id" => $data->id))',
									'label'     => Yii::t('wish_listing', '修改'),
									'options'   => array(
											'target'   => 'dialog',
											'class'    => 'btnEdit',
											'rel'      => 'aliexpressoverseaswarehouse-grid',
											'postType' => '',
											'callback' => '',
											'style'    => 'margin-left:60px;',
									),
							),
					),
			),												
						
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>