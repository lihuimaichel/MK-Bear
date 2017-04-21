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
		'value' => 'stripslashes($data->listing_title)',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '80px', 'align' => 'center',
		),
	),/*
	array(
		'name' => Yii::t('task', 'Sku Status'),
		'value' => '$data->listing_status',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),*/
	array(
		'name' => Yii::t('task', 'Sale Price'),
		'value' => '$data->sale_price',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Optimization Status'),
		'value' => '$data->status',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center',
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Watch Count'),
		'value' => '$data->watch_count',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Hit Count'),
		'value' => '$data->hit_count',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Listingid'),
		'value' => '$data->listing_id',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '20px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Listing Url'),
		'value' => 'CHtml::link("$data->listing_url","$data->listing_url", 
						array("title"=>$data->listing_url,"target"=>"_blank"))',

		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Accounts'),
		'value' => '$data->account_id',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
	array(
		'name' => Yii::t('task', 'Site'),
		'value' => '$data->site_name',
		'type' => 'raw',
		'headerHtmlOptions' => array(
			'class' => 'center', 'align' => 'center'
		),
		'htmlOptions' => array(
			'color' => 'blue', 'width' => '30px', 'align' => 'center',
		),
	),
    array(
        'name' => Yii::t('task', 'Optimization Date'),
        'value' => '$data->date_time',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
        ),
    ),
);

//如果是主管，添加上销售人员
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

//如果是组长，添加上销售人员
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