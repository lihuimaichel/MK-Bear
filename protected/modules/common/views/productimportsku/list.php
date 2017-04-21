<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'amazonasinimport-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text' => '导入SKU',
						'url' => Yii::app()->createUrl('/common/productimportsku/touploadfile'),
						'htmlOptions' 	=> array(
								'class' 	=> 'add',
								'target' 	=> 'dialog',
								'mask'		=>true,
								'rel' 		=> 'amazonasinimport-grid',
								'width' 	=> '500',
								'height' 	=> '300',
								'onclick' 	=> '',
						)
				),
				array(
						'text' => '各种状态中0表示未处理，1表示处理过。注意：未处理有可能会是未匹配到相应的listing',
						'url' => '#',
						'htmlOptions' 	=> array(
								'class' 	=> '',
								'style'		=>	'color:red;'
								)
						
				),
		),
		
	'columns' => array(
			array(
					'name' => 'ID',
					'value' => '$data->id',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'SKU',
					'value' => '$data->sku',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'AmazonStatus',
					'value' => '$data->amazon_status',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'EbayStatus',
					'value' => '$data->ebay_status',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'AliexpressStatus',
					'value' => '$data->aliexpress_status',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'LazadaStatus',
					'value' => '$data->lazada_status',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'WishStatus',
					'value' => '$data->wish_status',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>