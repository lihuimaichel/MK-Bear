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
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name' => 'item_id',
					'value' => '$data->item_id',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:140px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name' => 'sku',
					'value' => '$data->sku',
			        'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:100px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

         	array(
					'name'  => 'seller_sku',
					'value' => '$data->seller_sku',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name'  => 'account_id',
					'value' => '$data->account_name',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:60px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name'  => 'site_id',
					'value' => '$data->site_name',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:60px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name'  => 'new_item_id',
					'value' => '$data->new_item_id',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:140px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name'  => 'status',
					'value' => '$data->status',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:60px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name'  => 'message',
					'value' => '$data->message',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:200px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),

			array(
					'name' => 'create_user_id',
					'value' => 'MHelper::getUsername($data->create_user_id)',
					'type'  => 'raw',
					'htmlOptions' => array('style' => 'width:80px;'),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),	
			array(
					'name'  => 'create_time',
					'value' => '$data->create_time',
					'type'  => 'raw',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			),		
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>