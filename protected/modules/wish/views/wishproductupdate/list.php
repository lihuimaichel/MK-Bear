<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
    'id' => 'wish-product-update-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'toolBar' => array(
        array(
            'text' => Yii::t('wish_listing', 'Batch Delete'),
            'url' => Yii::app()->createUrl('/wish/wishproductupdate/batchdelete'),
            'htmlOptions' => array(
                'class' => 'delete',
                'title' => Yii::t('wish_listing', 'Are you sure to delete these?! Note:Only delete not upload success'),
                'target' => 'selectedTodo',
                'rel' => 'wish-product-update-grid',
                'postType' => 'string',
                'callback' => 'navTabAjaxDone',
                'onclick' => ''
            )
        ),


        array(
            'text' => Yii::t('wish', 'Batch Upload'),
            'url' => Yii::app()->createUrl('/wish/wishproductupdate/batchupload'),
            'htmlOptions' => array(
                'class' => 'add',
                'title' => Yii::t('wish', 'Confirm to upload listings?'),
                'target' => 'selectedTodo',
                'rel' => 'wish-product-update-grid',
                'postType' => 'string',
                'callback' => 'navTabAjaxDone',
                'onclick' => ''
            )
        ),
    ),
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'selectableRows' => 2,
            'value' => '$data->id',
            'disabled' => '$data->main_upload_status==2',
            'htmlOptions' => array(
                'attr-upload-status' => '$data->upload_status'
            )
        ),
        array(
            'name' => 'id',
            'value' => '$row+1',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:50px;',
            ),
        ),
        array(
            'name' => 'listing_id',
            'value' => 'VHelper::getBoldShow($data->listing_id)',
            'type' => 'raw',
            'htmlOptions' => array('style' => 'width:180px;'),
        ),


        array(
            'name' => 'name',
            'value' => 'VHelper::getBoldShow($data->name)',
            'type' => 'raw',
            'htmlOptions' => array('style' => 'width:350px;'),
        ),


        array(
            'name' => 'upload_times',
            'value' => '$data->upload_times',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:70px;',
            ),
        ),
        array(
            'name' => 'upload_status',
            'value' => '$data->upload_status',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:70px;',
            ),
        ),
        array(
            'name' => 'last_upload_time',
            'value' => '$data->last_upload_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:70px;',
            ),
        ),
        array(
            'name' => 'create_time',
            'value' => '$data->create_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:70px;',
            ),
        ),
        array(
            'name' => 'create_user_id',
            'value' => 'MHelper::getUsername($data->create_user_id)',
            'htmlOptions' => array(
                'style' => 'text-align:center;width:70px;',
            ),
        ),

        array(
            'name' => 'sku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'online_sku',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'upload_action',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'upload_status',
            'value' => array($this, 'renderGridCell'),
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'actionText',
            'value' => '$data->actionText',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px',
                'align' => 'center'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
    ),
    'tableOptions' => array(
        'layoutH' => 90,
    ),
    'pager' => array(),
);

$this->widget('UGridView', $options);

?>