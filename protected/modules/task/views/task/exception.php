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
$column = array(
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
        'name' => Yii::t('task', 'Sku'),
        'value' => '$data->sku',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
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
        'name' => Yii::t('task', 'Accounts'),
        'value' => '$data->account_short_name',
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
        'value' => '$data->site_name',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
        ),
    ),
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
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
        ),
    ),
    array(
        'name' => Yii::t('task', 'Appeal Status'),
        'value' => '$data->appeal_status',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
        ),
    ),
    array(
        'name' => Yii::t('task', 'Appeal Message'),
        'value' => '$data->appeal_description',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '50px', 'align' => 'center',
        ),
    ),
    array(
        'name' => Yii::t('task', 'Task Date Time'),
        'value' => '$data->date_time',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
        ),
    ),
    array(
        'name' => Yii::t('task', 'Appeal Date Time'),
        'value' => '$data->appeal_time',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
        ),
    ),
);

$appeal_url = 'Yii::app()->createUrl("/task/task/processappeal",array("id" =>$data->id))';
$column = array_merge($column,
    array(
        array(
            'header' => Yii::t('system', 'Operation'),
            'class' => 'CButtonColumn',
            'template' => '{operate}',
            'htmlOptions' => array(
                'style' => 'text-align:center;',
            ),
            'buttons' => array(
                'operate' => array(
                    'url' => $appeal_url,
                    'label' => Yii::t('task', 'Operate'),
                    'options' => array(
                        'title' => Yii::t('task', 'Operate'),
                        'mask' => true,
                        'target' => 'dialog',
                        'rel' => 'page_operate_appeal',
                        'width' => '650',
                        'height' => '500',
                    ),
                    'visible' => '$data->status_value'
                ),
            ),
        ),
    )
);
$this->widget('UGridView', array(
	'id' => 'task_sales_target',
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