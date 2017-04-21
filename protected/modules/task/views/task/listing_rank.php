<?php
/**
 * Created by PhpStorm.
 * User: wuyk
 * Date: 2017/1/23
 * Time: 15:42
 *
 * 刊登排行
 */

Yii::app()->clientScript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'task_sales_target',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'columns' => array(
		array(
			'name' => Yii::t('task','Id'),
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:40px',
			),
		),
		array(
			'name' => Yii::t('task','Seller'),
			'value' => '$data->seller_name',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '40', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Listing Num'),
			'value' => '$data->listing_num',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '50', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Listing Finish'),
			'value' => '$data->was_listing_num',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '30', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Listing Rate'),
			'value' => '$data->listing_rate',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '30', 'align' => 'center',
			),
		),
		array(
			'name' => Yii::t('task','Rank'),
			'value' => '$data->rank',
			'type'  => 'raw',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'color' => 'blue','width' => '30', 'align' => 'center',
			),
		),
	),
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),
));
?>