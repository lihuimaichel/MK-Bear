<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'ebay_account_share_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'class'=>'CCheckBoxColumn',
            'value'=>'$data->id',
            'selectableRows' => 2,
            'htmlOptions' => array(
                    'style' => 'width:40px;',
            
            ),
            'headerHtmlOptions' => array(
                    'align' => 'center',
                    'style' => 'width:40px;',
                    'onclick'=>''
            ),
            'checkBoxHtmlOptions' => array(
                    'onchange' => '',
                    'onpropertychange' => '',
                    'oninput' => '',
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
            'name' => 'department_id',
            'value' => '$data->department_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:120px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'seller_id',
            'value' => 'MHelper::getUsername($data->seller_id)',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:120px'
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
                'style' => 'width:200px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'end_time',
            'value' => '$data->end_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px'
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
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),    

    ),
    'toolBar' => array(
        array(
                'text' => Yii::t('system', 'Add'),
                'url' => Yii::app()->createUrl('ebay/ebayaccountshare/add'),
                'htmlOptions' => array(
                        'class'     => 'add',
                        'target'    => 'dialog',
                        'rel'       => 'ebay_account_share_widget',
                        'postType'  => '',
                        'callback'  => '',
                        'width'     => '500',
                        'height'    => '400',
                )
        ),
        array(
                'text' => Yii::t('system', 'Batch delete messages'),
                'url' => Yii::app()->createUrl('ebay/ebayaccountshare/batchdel'),
                'htmlOptions' => array (
                        'class' => 'delete',
                        'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
                        'target' => 'selectedTodo',
                        'rel' => 'ebay_account_share_widget',
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