<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$column = array(
	array(
		'class' => 'CCheckBoxColumn',
		'value' => '$data->id',
		'selectableRows' => 2,
		'headerHtmlOptions' => array(
			'style' => 'width:25px',
		),
	),
	array(
		'name' => Yii::t('task', 'Id'),
		'value' => '$row+1',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'style' => 'width:40px',
		),
	),
	array(
		'name' => Yii::t('task', 'Sku'),
		'value' => '$data->sku',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '40px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Title'),
		'value' => 'stripslashes($data->sku_title)',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '80px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Accounts'),
		'value' => 'stripslashes($data->account_short_name)',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '20px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Site'),
		'value' => 'stripslashes($data->site_name)',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	/*
	array(
		'name' => Yii::t('task', 'Sku Status'),
		'value' => '$data->sku_status',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Sales Price'),
		'value' => '$data->cost_price',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),*/
	array(
		'name' => Yii::t('task', 'Company Category'),
		'value' => '$data->sku_category_id',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Product Category'),
		'value' => '$data->category_name',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Listing Status'),
		'value' => '$data->status',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
    array(
        'name' => Yii::t('task', 'Listing Date'),
        'value' => '$data->date_time',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
        ),
    ),
	array(
		'name' => Yii::t('task', 'Listing Time'),
		'value' => '$data->added_at',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '40', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'System Task'),
		'value' => '$data->is_system',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '40', 'align' => 'center',
		),
	)
);

if ($model->group()) {
	if (ProductsGroupModel::GROUP_LEADER == $model->group()->job_id) {
		$column = array_merge($column,
			array(
				array(
					'name' => Yii::t('task', 'Seller'),
					'value' => '$data->seller_user_id',
					'type' => 'raw',
					'headerHtmlOptions' => array(
						'class' => 'center', 'align' => 'center'
					),
					'htmlOptions' => array(
						'color' => 'blue', 'width' => '40', 'align' => 'center',
					),
				)
			)
		);
	}
}

$check_result = AuthAssignment::model()->checkCurrentUserIsAdminister(Yii::app()->user->id, $platform);
if ($check_result && !$model->group()) {
	$column = array_merge($column,
		array(
			array(
				'name' => Yii::t('task', 'Seller'),
				'value' => '$data->seller_user_id',
				'type' => 'raw',
				'headerHtmlOptions' => array(
					'class' => 'center', 'align' => 'center'
				),
				'htmlOptions' => array(
					'color' => 'blue', 'width' => '40', 'align' => 'center',
				),
			)
		)
	);
}


$this->widget('UGridView', array(
	'id' => 'task_today_listing',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => $column,
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),
));
?>
