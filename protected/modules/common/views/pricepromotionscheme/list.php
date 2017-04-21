<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'promotion_scheme_list',
	'filter' => $model,
	'dataProvider' => $model->search(),
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'selectableRows' => 2,
			'value' => '$data->id',
			'headerHtmlOptions' => array(
					'align' => 'center',
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
				'style' => 'width: 25px',
			),
		),
		array(
			'name' => 'name',
			'value' => '$data->name',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 300px',
			),
		),
		array(
			'name' => 'platform_code',
			'value' => '$data->platform_name',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 200px',
			),				
		),
		array(
			'name' => 'discount_mode',
			'value' => '$data->discount_mode',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),
		array(
			'name' => 'discount_factor',
			'value' => '$data->discount_factor',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),
		array(
			'name' => 'start_date',
			'value' => '$data->start_date',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 135px',
			),
		),
		array(
			'name' => 'end_date',
			'value' => '$data->end_date',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 135px',
			),
		),
		array(
			'name' => 'status',
			'value' => '$data->status',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 50px',
			),
		),
		array(
			'name' => 'create_user_id',
			'value' => 'MHelper::getUsername($data->create_user_id)',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:100px',
			)				
		),
		array(
			'name' => 'create_time',
			'value' => '$data->create_time',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:135px',
			)
		),
		array(
			'name' => 'progress',
			'value' => '$data->progress',
			'headerHtmlOptions' => array('class' => 'center',),
			'htmlOptions' => array(
				'style' => 'width:135px',
			)
		),																
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('system', 'Add'),
			'url' => Yii::app()->createUrl('/common/pricepromotionscheme/create'),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'dialog',
				'width' => 750,
				'height' => 650,
				'callback' => 'navTabAjaxDown',
				'onclick' => '',
			),
 		),			
	),
	'tableOptions' => array(
		'layoutH' => 145,
		'tableFormOptions' => true
	),
	'pager' => array(),		
));