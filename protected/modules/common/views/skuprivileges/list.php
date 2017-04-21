<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'productimagesetting_id',
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
			'name' => 'sku',
			'value' => '$data->sku',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),	
		),
		array(
			'name' => 'title',
			'value' => '$data->title',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 375px',
			),
		),			
		array(
			'name' => 'platform_id',
			'value' => '$data->platform_name',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),
		array(
			'name' => 'account_id',
			'value' => '$data->account_name',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),
		array(
			'name' => 'username',
			'value' => '$data->username',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width: 100px',
			),
		),										
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('sku_privileges', 'Change Privileges'),
			'url' => Yii::app()->createUrl('common/skuprivileges/chooseuser'),
			'htmlOptions' => array(
				'class' => 'add',
				'target' => 'dialog',
				'width' => 350,
				'height' => 225,
				'mask' => true,
				'onclick' => '',
				 
			),
		),	
	),
	'pager' => array(),
));