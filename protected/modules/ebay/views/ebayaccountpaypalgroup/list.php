<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
    'id' => 'ebay_account_paypal_group_widget',
    'filter' => $model,
    'dataProvider' => $model->search(),
    'selectableRows' => 2,
    'columns' => array(
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
            'name' => 'group_name',
            'value' => '$data->group_name',
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
                'style' => 'width:80px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'rule',
            'value' => '$data->rule',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:520px'
            ),
            'headerHtmlOptions' => array(
                //'align' => 'center'
            ),
        ),
        array(
            'name' => 'account_name',
            'value' => '$data->account_name',
            'type' => 'raw',
            'htmlOptions' => array(
                'style' => 'width:200px'
            ),
            'headerHtmlOptions' => array(
                'align' => 'center'
            ),
        ),

        array(
            'name' => 'add_time',
            'value' => '$data->add_time',
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
                'template' => '{edit}&nbsp;&nbsp;{addAccount}',
                'htmlOptions' => array(
                        'style' => 'text-align:center;width:160px;',
                ),
                'buttons' => array(
                        'edit' => array(
                                'url'       => 'Yii::app()->createUrl("/ebay/ebayaccountpaypalgroup/update", array("id" => $data->id))',
                                'label'     => '修改分组规则',
                                'options'   => array(
                                        'target'    =>  'dialog',
                                        'rel'       => 'ebay_account_paypal_group_widget',
                                        'class'     =>  'btnEdit',
                                        'height'    => '680',
                                        'width'     => '600' ,
                                        'postType' => '',
                                        'callback' => '',
                                ),
                        ),
                        'addAccount' => array(
                                'url'       => 'Yii::app()->createUrl("/ebay/ebayaccountpaypalgroup/addaccount", array("id" => $data->id))',
                                'label'     => Yii::t('ebay', '添加帐号'),
                                'options'   => array(
                                        'target'    =>  'dialog',
                                        'rel'       => 'ebay_account_paypal_group_widget',
                                        'height'    => '680',
                                        'width'     => '780' ,
                                        'postType' => '',
                                        'callback' => '',
                                ),
                        ),
                ),
        ),

    ),
    'toolBar' => array(
            array (
                    'text' => Yii::t ( 'system', 'Add' ),
                    'url' => '/ebay/ebayaccountpaypalgroup/add',
                    'htmlOptions' => array (
                            'class' => 'add',
                            'target' => 'dialog',
                            'rel' => 'ebay_account_paypal_group_widget',
                            'postType' => '',
                            'callback' => '',
                            'height' => '680',
                            'width' => '600' 
                    ) 
            ) 

        ),
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true
    )

));
?>