<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$group = $this->group();
$column_date_time = array(
	array(
		'name' => Yii::t('task','Year Month'),
		'value' => '$data->date_time',
		'type'  => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),

		'htmlOptions' => array(
			'color' => 'blue', 'style' => 'width:200px','align' => 'center',
		),
	)
);

$column_seller = array(
	array(
		'name' => Yii::t('task','Seller'),
		'value' => '$data->seller_name',
		'type'  => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'style' => 'width:200px','align' => 'center',
		),
	)
);

if ($group) {
	if (ProductsGroupModel::GROUP_SALE == $group->job_id) {
		$merge_column = $column_date_time;
	} else {
		$merge_column = array_merge($column_date_time, $column_seller);
	}
    $tool_bar_column = array();
} else {
	$merge_column = array_merge($column_date_time, $column_seller);
    $tool_bar_column = array(
        array(
            'text' => Yii::t('task', 'Group Total'),
            'url' => '/systems/salesprofit/group',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_record',
                'title' => Yii::t('task', 'Group Total'),
            ),
        ),

        array(
            'text' => Yii::t ( 'task', 'Department Total' ),
            'url'  => '/systems/salesprofit/department',
            'htmlOptions' => array (
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_record',
                'title' => Yii::t('task', 'Department Total'),
            )
        ),
    );
}


$column = array_merge($merge_column , array(
		array(
			'name' => Yii::t('task','Sales Target'),
			'value' => '$data->sales_target',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Sales Amount'),
			'value' => '$data->sales_amount_rmb',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Sales Rate'),
			'value' => '$data->sales_rate',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Profit Target'),
			'value' => '$data->profit_target',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Retained Profits'),
			'value' => '$data->retained_profits',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task', 'Profit Rate'),
			'value' => '$data->profit_rate',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','style' => 'width:200px', 'align' => 'center',
			),
		),
	)
);

$this->widget('UGridView', array(
	'id' => 'sales_profit_index',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => $column,
	'toolBar' => $tool_bar_column,
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),
));
?>
