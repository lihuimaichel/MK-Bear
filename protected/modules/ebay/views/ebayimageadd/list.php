<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'ebay_image_add_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
                'class' => 'CCheckBoxColumn',
                'selectableRows' =>2,
                'value'=> '$data->id',
                'htmlOptions' => array(
                    'width' => '20'
                )
        ),
        array(
            'name' => 'ID',
            'value' => '$data->id',

            'htmlOptions' => array(
                'style' => 'width:100px',
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
            'name' => 'image_name',
            'value' => '$data->image_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:130px'
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
                'style' => 'width:60px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'site_id',
            'value' => '$data->site_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:60px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'type',
            'value' => '$data->type',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'upload_status',
            'value' => '$data->upload_status',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'local_path',
            'value' => '$data->local_path',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'remote_path',
            'value' => '$data->remote_path',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px'
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
                'style' => 'width:160px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        

    ),
    'toolBar' => array(
            array(
                    'text' => Yii::t('system', 'Batch delete messages'),
                    'url' => Yii::app()->createUrl('ebay/ebayimageadd/batchdel'),
                    'htmlOptions' => array (
                                'class' => 'delete',
                                'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
                                'target' => 'selectedTodo',
                                'rel' => 'ebay_image_add_widget',
                                'postType' => 'string',
                                'warn' => Yii::t ( 'system', 'Please Select' ),
                                'callback' => 'navTabAjaxDone' 
                    ) 
            ),

        ),
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true
    )

));
?>