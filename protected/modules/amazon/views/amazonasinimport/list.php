<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'amazonasinimport-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text' => Yii::t('amazon_product', 'Import Asin'),
						'url' => Yii::app()->createUrl('/amazon/amazonasinimport/touploadfile'),
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
					'name' => 'EncryptSKU',
					'value' => '$data->sku_encrypt',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ASIN',
					'value' => '$data->asin',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'AccountID',
					'value' => '$data->account_id',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>