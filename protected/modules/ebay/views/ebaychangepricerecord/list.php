<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'ebay_price_record_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
        array(
            'class'=>'CCheckBoxColumn',
            'value'=>'$data->id',
            'selectableRows' => 2,
            'htmlOptions' => array(
                    'style' => 'width:20px;',
            
            ),
            'headerHtmlOptions' => array(
                    'align' => 'center',
                    'style' => 'width:20px;',
                    'onclick'=>''
            ),
            'checkBoxHtmlOptions' => array(
                    'onchange' => '',
                    'onpropertychange' => '',
                    'oninput' => '',
            ),
                
        ),
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
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'sku_online',
            'value' => '$data->sku_online',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:180px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'item_id',
            'value' => '$data->item_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:140px'
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
            'name' => 'site_id',
            'value' => '$data->site_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:50px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'seller_name',
            'value' => '$data->seller_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        /*array(
            'name' => 'condition_type',
            'value' => '$data->condition_type',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),*/
        array(
            'name' => 'type',
            'value' => '$data->type',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:120px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'old_price',
            'value' => '$data->old_price',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'old_profit_rate',
            'value' => '$data->old_profit_rate',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'new_price',
            'value' => '$data->new_price',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'new_profit_rate',
            'value' => '$data->new_profit_rate',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'deal_date',
            'value' => '$data->deal_date',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
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
                'style' => 'width:200px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'last_response_time',
            'value' => '$data->last_response_time',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:100px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'header' => Yii::t('system', 'Operation'),
            'class' => 'CButtonColumn',
            'template' => '{update1}',
            'htmlOptions' => array(
                    'style' => 'text-align:center;width:100px;',
            ),
            'buttons' => array(
                        
                    'update1' => array(
                        'url'       => 'Yii::app()->createUrl("/ebay/ebaychangepricerecord/uploadprice", array("ids" => $data->id))',
                        'label'     => Yii::t('aliexpress', 'Upload Now'),
                        'options'   => array(
                                'title'     => Yii::t('aliexpress', 'Are you sure to upload these'),
                                'target'    => 'ajaxTodo',
                                'rel'       => 'ebay_price_record_widget',
                                'postType'  => 'string',
                                'callback'  => 'navTabAjaxDone',
                                'onclick'   =>  '',
                                'style'     =>  'width:80px;height:28px;line-height:28px;'
                        ),
                        'visible'   =>  '$data->visiupload'
                    ),
            ),
        ),
        array(
            'name' => 'update_user_id',
            'value' => '$data->update_user_id',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

    ),
    'toolBar' => array(
        array(
            'text' => Yii::t('ebay', 'Batch Upload'),
            'url' => Yii::app()->createUrl('ebay/ebaychangepricerecord/uploadprice'),
            'htmlOptions' => array (
                'class' => 'add',
                'title' => Yii::t ( 'ebay', 'Really want to upload these records?' ),
                'target' => 'selectedTodo',
                'rel' => 'ebay_price_record_widget',
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