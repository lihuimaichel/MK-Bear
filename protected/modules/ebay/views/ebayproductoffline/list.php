<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'ebayproductoffline-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(),
	'columns'=>array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'	=> '$data->id',
					'htmlOptions'=>array(
							'style' => 'width:60px;'
								)
			),

			array(
					'name' => 'item_id',
					'value' => '$data->item_id',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),

			array(
					'name' => 'sku',
					'value' => '$data->sku',
			        'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:180px;'),
			),

         	array(
					'name'  => 'seller_sku',
					'value' => '$data->seller_sku',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
			),

			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
			),

			array(
					'name'  => 'site_id',
					'value' => '$data->site_name',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
			),

			array(
					'name'  => 'create_time',
					'value' => '$data->create_time',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
			),
			
			array(
					'name' => 'create_user_id',
					'value' => 'MHelper::getUsername($data->create_user_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
			),			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>