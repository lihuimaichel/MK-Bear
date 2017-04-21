<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'priceminister_product_statistic',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->sku',
			'selectableRows' => 2,
			'headerHtmlOptions' => array(
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'id',
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:25px',
			),
		),
		array(
			'name' => 'sku',
			'value' => '$data->sku',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
					'color' => 'blue','width' => '40', 'align' => 'center',
			),			
		),
		array(
			'name' => 'en_title',
			'value' => 'VHelper::getBoldShow(!empty($data->en_title) ? $data->en_title : $data->cn_title)',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center','align' => 'center'
			),
			'htmlOptions' => array(
				'style' => 'width:275px',
			),				
		),
		array(
			'name' => 'product_cost',
				'value'=> 'VHelper::getRedBoldShow($data->product_cost)',
				'type'  => 'raw',
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'width' => '40', 'align' => 'center',
				),
		),
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('aliexpress_product_statistic', 'Batch Publish'),
			'url' => Yii::app()->createUrl('priceminister/priceministerproductstatistic/batchpublish/', array( 'account_id' => $accountID)),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'selectedTodo',
				'rel' => 'priceminister_product_statistic',
				'postType' => 'string',
				'callback' => 'navTabAjaxDone',
				'title' => Yii::t('priceminister', 'Are You Sure to Publish?'),
				'onclick' => '',
			),
		),			
	),
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),		
));