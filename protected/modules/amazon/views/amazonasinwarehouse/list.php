<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'amazonasinwarehouse-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text' => Yii::t('amazon_product', 'Import Asin'),
						'url' => Yii::app()->createUrl('/amazon/amazonasinwarehouse/touploadfile'),
						'htmlOptions' 	=> array(
								'class' 	=> 'add',
								'target' 	=> 'dialog',
								'mask'		=> true,
								'rel' 		=> 'amazonasinwarehouse-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),
				array(
						'text' => Yii::t('amazon_product', 'Download Temple'),
						'url' => Yii::app()->createUrl('/amazon/amazonasinwarehouse/downloadtp'),
						'htmlOptions' 	=> array(
								'class' 	=> 'icon',
								'target' 	=> '_blank',
								'mask'		=> true,
								'rel' 		=> 'amazonasinwarehouse-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),	
				array(
						'text' => Yii::t('amazon_product', 'Delete Data'),
						'url' => Yii::app()->createUrl('/amazon/amazonasinwarehouse/delete'),
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('amazon_product', 'Really want to delete the data'),
								'target'    => 'selectedTodo',
								'rel'       => 'amazonasinwarehouse-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				)							
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
					'name' => 'seller_sku',
					'value' => '$data->seller_sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),				
			array(
					'name' => 'overseas_warehouse_id',
					'value' => '$data->overseas_warehouse_id',
					'htmlOptions' => array('style' => 'width:150px;'),
			),						
			array(
					'name' => 'ASIN',
					'value' => '$data->asin',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'seller',
					'value' => '$data->seller',
					'htmlOptions' => array('style' => 'width:150px;'),
			),	
			array(
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			)						
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>