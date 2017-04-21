<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'wish_listing_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'name' => 'ID',
            'value' => '$data->id',

            'htmlOptions' => array(
                'style' => 'width:60px',
                'align' => 'center',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
                'onclick' => '',
            ),

        ),
        array(
            'name' => 'sku',
            'value' => '$data->sku',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'product_id',
            'value' => '$data->product_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:120px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'account_id',
            'value' => '$data->account_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'old_quantity',
            'value' => '$data->old_quantity',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'set_quantity',
            'value' => '$data->set_quantity',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'status',
            'value' => '$data->status',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'msg',
            'value' => '$data->msg',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:300px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'create_time',
            'value' => '$data->create_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'create_user_id',
            'value' => '$data->create_user_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'import_id',
            'value' => '$data->import_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'update_time',
            'value' => '$data->update_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

    ),
    'toolBar' => array(),
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true
    )

));
?>