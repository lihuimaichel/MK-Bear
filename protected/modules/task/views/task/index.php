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
            'color' => 'blue', 'width' => '30px', 'align' => 'center',
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
        'name' => Yii::t('task', 'Title'),
        'value' => 'stripslashes($data->sku_title)',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center',
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '120px', 'align' => 'center',
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
        'name' => Yii::t('task', 'Cost Price'),
        'value' => '$data->cost_price',
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
        'name' => Yii::t('task', 'Listing Status'),
        'value' => '$data->status',
        'type' => 'raw',
        'headerHtmlOptions' => array(
            'class' => 'center', 'align' => 'center'
        ),
        'htmlOptions' => array(
            'color' => 'blue', 'width' => '20px', 'align' => 'center',
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
);

switch ($platform) {
    case Platform::CODE_EBAY:
        $url = 'Yii::app()->createUrl("/ebay/ebayproductadd/productaddstepsecond",array("sku" => $data->sku, "account_id"=>$data->account_id,"site_id"=>$data->site_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/ebay/ebayproductadd/productaddstepfirst');
        break;
    case Platform::CODE_ALIEXPRESS:
        $url = 'Yii::app()->createUrl("/aliexpress/aliexpressproductadd/step2",array("publish_sku" => $data->sku, "account_id"=>$data->account_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/aliexpress/aliexpressproductadd/step1');
        break;
    case Platform::CODE_WISH:
        $url = 'Yii::app()->createUrl("/wish/wishproductadd/add",array("sku" => $data->sku, "account_id"=>$data->account_id, "warehouse_id"=>$data->warehouse_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/wish/wishproductadd/index');
        break;
    case Platform::CODE_AMAZON:
        $url = 'Yii::app()->createUrl("/amazon/amazonproductadd/step2",array("publish_sku" => $data->sku, "account_id"=>$data->account_id, "site_id"=>$data->site_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/amazon/amazonproductadd/step1');
        break;
    case Platform::CODE_LAZADA:
        $url = 'Yii::app()->createUrl("/lazada/lazadaproductadd/productaddstepsecond",array("sku" => $data->sku, "account_id"=>$data->account_id, "site_id"=>$data->site_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/lazada/lazadaproductadd/productaddstepfirst');
        break;
    default:
        $url = 'Yii::app()->createUrl("/ebay/ebayproductadd/productaddstepsecond",array("sku" => $data->sku, "account_id"=>$data->account_id,"site_id"=>$data->site_id))';
        $rel_page = "page".Menu::model()->getIdByUrl('/ebay/ebayproductadd/productaddstepfirst');
        break;
}

$appeal_url = 'Yii::app()->createUrl("/task/task/appeal",array("id" =>$data->id))';
$un_appeal_url = 'Yii::app()->createUrl("/task/task/unappeal",array("id" =>$data->id))';
//$un_appeal_id = $data->id;

if ($model->group()) {
    if (ProductsGroupModel::GROUP_LEADER == $model->group()->job_id) {
        $param = Yii::app()->request->getParam('param');
        if ('history' != $param) {
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
                    ),
                )
            );
        } else {
            $column = array_merge($column,
                array(
                    array(
                        'name' => Yii::t('task', 'Task Update Time'),
                        'value' => '$data->updated_at',
                        'type' => 'raw',
                        'headerHtmlOptions' => array(
                            'class' => 'center', 'align' => 'center'
                        ),
                        'htmlOptions' => array(
                            'color' => 'blue', 'width' => '20px', 'align' => 'center',
                        ),
                    ),
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
                    ),
                )
            );
        }

    } else {
        $param = Yii::app()->request->getParam('param');
        if ('history' != $param) {
            $column = array_merge($column,
                array(
                    array(
                        'header' => Yii::t('system', 'Operation'),
                        'class' => 'CButtonColumn',
                        'template' => '{listing} &nbsp;&nbsp;{appeal}&nbsp;&nbsp;{unappeal}',
                        'htmlOptions' => array(
                            'style' => 'text-align:center;',
                        ),
                        'buttons' => array(
                            'listing' => array(
                                'url' => $url,
                                'label' => Yii::t('task', 'Listing'),
                                'options' => array(
                                    'title' => Yii::t('task', 'Listing'),
                                    'target' => 'navTab',
                                    'rel' => $rel_page,
                                    'style' => 'width:40px;height:28px;line-height:28px;'
                                ),
                                'visible' => '$data->status_value'
                            ),
                            'appeal' => array(
                                'url' => $appeal_url,
                                'label' => Yii::t('task', 'Appeal'),
                                'options' => array(
                                    'title' => Yii::t('task', 'Appeal'),
                                    'mask' => true,
                                    'target' => 'dialog',
                                    'rel' => 'page_listing_add',
                                    'width' => '650',
                                    'height' => '500',
                                    //'style' => 'width:800px;height:600px;'
                                ),
                                'visible' => '$data->status_value'
                            ),
                            'unappeal' => array(
                                'url'       => 'Yii::app()->createUrl("/task/task/unappeal", array("id" => $data->id))',
                                'label' => Yii::t('task', 'Unappeal'),
                                'options'   => array(
                                    'title' => Yii::t('task', 'Unappeal'),
                                    'target'    => 'ajaxTodo',
                                    'rel'       => 'page_listing_add',
                                    'postType'  => 'string',
                                    'callback'  => 'navTabAjaxDone',
                                    'onclick'	=>	'',
                                    'style'		=>	'width:80px;height:28px;line-height:28px;'
                                ),
                                'visible' => '$data->appeal_status'
                            ),
                        ),
                    ),
                )
            );
        } else {
            $column = array_merge($column, array(
                array(
                    'name' => Yii::t('task', 'Task Update Time'),
                    'value' => '$data->updated_at',
                    'type' => 'raw',
                    'headerHtmlOptions' => array(
                        'class' => 'center', 'align' => 'center'
                    ),
                    'htmlOptions' => array(
                        'color' => 'blue', 'width' => '20px', 'align' => 'center',
                    ),
                ),
            ));
        }
    }
}

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

$toolBar = array(
    'id' => 'task_today_listing',
    'dataProvider' => $model->search(),
    'filter' => $model,
    'columns' => $column,
    'pager' => array(),
    'tableOptions' => array(
        'layoutH' => 150,
        'tableFormOptions' => true
    ),
);

if ('history' != Yii::app()->request->getParam('param')) {
    $toolArr = array(
        array(
            'text' => Yii::t('task', 'History Listing'),
            'url' => '/task/task/index/param/history',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_index',
                'title' => Yii::t('task', 'History Listing'),
            ),
        ),
        array(
            'text' => Yii::t('task', 'Was Listing'),
            'url' => '/task/task/listinghistory',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_listing_history',
                'title' => Yii::t('task', 'Was Listing'),
            ),
        ),
    );
    if ($model->group()) {
        if(ProductsGroupModel::GROUP_SALE == $model->group()->job_id) {
            $toolArr = array_merge($toolArr, array(
                array(
                    'text' => Yii::t('task', 'Apply Task'),
                    'url' => '/task/task/applytask',
                    'htmlOptions' => array(
                        'class' => 'apply_task',
                        'title' => Yii::t('task', 'Apply Task').'？',
                        'target' => 'ajaxTodo',
                        'rel' => 'page_task_index',
                        'postType' => 'string',
                        'callback' => 'navTabAjaxDone'
                    ),
                ),
            ));
        }
    }

    $mergeData = array(
        'toolBar' => $toolArr
    );
} else {
    $toolArr = array(
        array(
            'text' => Yii::t('task', 'Current Listing'),
            'url' => '/task/task/index',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_task_index',
                'title' => Yii::t('task', 'Current Listing'),
            ),
        ),
        array(
            'text' => Yii::t('task', 'Was Listing'),
            'url' => '/task/task/listinghistory',
            'htmlOptions' => array(
                'class' => '',
                'target' => 'navTab',
                'rel' => 'page_listing_history',
                'title' => Yii::t('task', 'Was Listing'),
            ),
        ),
    );
    $mergeData = array(
        'toolBar' => $toolArr,
    );
}

$this->widget('UGridView', array_merge($toolBar, $mergeData));
?>
