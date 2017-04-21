<?php
Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$column = array(
	array(
		'name' => Yii::t('task', 'Date'),
		'value' => '$data->date_time',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'style' => 'width:150px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Listing Num'),
		'value' => '$data->listing_num',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Listing Finish'),
		'value' => '$data->finish_listing_num',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '20px', 'align' => 'center',
		),
	),
	array(
        'name' => Yii::t('task', 'Listing Rate'),
        'value' => '$data->listing_rate',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
        ),
	),
	array(
		'name' => Yii::t('task', 'Optimization Num'),
		'value' => '$data->optimization_num',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '20px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Optimization Finish'),
		'value' => '$data->finish_optimization_num',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
    array(
        'name' => Yii::t('task', 'Optimization Rate'),
        'value' => '$data->optimization_rate',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
        ),
    ),
	array(
		'name' => Yii::t('task', 'Seven Sales'),
		'value' => '$data->seven_listing',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Seven Optimization Sales'),
		'value' => '$data->seven_optimization',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Score'),
		'value' => '$data->score',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
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
    'toolBar' => array(
        array(
            'text' => Yii::t('task', 'Sales Month'),
            'url' => '/task/task/recordmonth',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_record',
                'title' => Yii::t('task', 'Sales Month'),
            ),
        ),
        array(
            'text' => Yii::t('task', 'Sales Year'),
            'url' => '/task/task/recordyear',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_record',
                'title' => Yii::t('task', 'Sales Year'),
            ),
        ),
    ),
));
?>
