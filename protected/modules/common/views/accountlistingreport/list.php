<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$columns = array(
	array(
		'class' => 'CCheckBoxColumn',
		'value' => '$data->iid',
		'selectableRows' => 2,
		'headerHtmlOptions' => array(
			'align' => 'center',
			'style' => 'width:25px'
		),
		'htmlOptions' => array(
			'style' => 'width:25px'
		)
	),
	array(
		'name' => 'iid',
		'value' => '$row+1',
		'htmlOptions' => array(
			'align' => 'center',
			'style' => 'width:50px',
		),
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
	),
	array(
		'name' => 'sku',
		'value' => '$data->sku',
		'htmlOptions' => array(
			'style' => 'width:100px',
		),
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
	),
	array(
		'name' => 'report_date',
		'value' => '$data->report_date',
		'htmlOptions' => array(
			'align' => 'center',
			'style' => 'width:100px',
		),
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
	),			
);
if (!empty($hasPrivilegesColumns)) {
	foreach ($hasPrivilegesColumns as $key => $column) {
		$columnName = isset($accountColumnMaps[$column]) ? $accountColumnMaps[$column] : '';
		$columns[] = array(
			'name' => $column,
			'type' => 'html',
			//'value' => 'processColumnData($data, $row, $key)',
			'htmlOptions' => array(
					'align' => 'center',
					'style' => 'width:75px',
			),
			'header' => $columnName,
			'headerHtmlOptions' => array(
					'class' => 'center',
			),			
		);
	}
}
$this->widget('UGridView', array(
	'id' => 'account_listing_report',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => $columns,
	'toolBar' => array(
	),
	'tableOptions' 	=> array(
		'layoutH' 	=> 65,
	),		
	'pager' => array(
	),		
));
?>