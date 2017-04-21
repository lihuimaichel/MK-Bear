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
            'name' => 'seller_sku',
            'value' => '$data->seller_sku',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:140px'
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
                'style' => 'width:180px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'account_name',
            'value' => '$data->account_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:110px'
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
            'name' => 'seller_user_id',
            'value' => 'User::model()->getUserNameScalarById($data->seller_user_id)',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'change_where',
            'value' => '$data->change_where',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:120px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'type',
            'value' => 'LazadaChangePriceRecord::model()->getTypeOptions($data->type)',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
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
            'name' => 'status',
            'value' => 'LazadaChangePriceRecord::model()->getStatusOptions($data->status)',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
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
                        'url'       => 'Yii::app()->createUrl("/lazada/lazadachangepricerecord/uploadprice", array("ids" => $data->id))',
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
                        'visible'   =>  '$data->is_visible == 1'
                    ),
            ),
        ),
        array(
            'name' => 'message',
            'value' => '$data->message',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),
        array(
            'name' => 'update_user_id',
            'value' => 'User::model()->getUserNameScalarById($data->update_user_id)',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'run_count',
            'value' => '$data->run_count',
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
            'url' => Yii::app()->createUrl('/lazada/lazadachangepricerecord/uploadprice'),
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