<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'shopee_exclude_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
        array(
            'class' => 'CCheckBoxColumn',
            'value' => '$data->id',
            'selectableRows' => 2,
            'htmlOptions' => array(
                'style' => 'width:20px',
                'align' => 'center',
            ),
            'headerHtmlOptions' => array(
                'align' => 'center',
                'onclick' => 'allSelect(this)',
            ),
            'checkBoxHtmlOptions' => array(
                'onchange' => 'checkSelect(this)',
                //'onclick'=>'checkSelect(this)',
                'oninput' => 'checkSelect(this)',
                'onpropertychange' => 'checkSelect(this)'
            )
        ),
			array(
					'name'=>'sku',
					'value'=>'$data->sku',
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:180px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'account_id',
					'value'=>'$data->account_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:120px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'status',
					'value'=>'$data->status',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			

			array(
					'name'=>'created_at',
					'value'=>'$data->created_at',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			
	),
	'toolBar'=>array(

        array(
            'text' => Yii::t('shopee', 'Import SKU List'),
            'url' => 'javascript:void(0)',
            'htmlOptions' => array(
                'class' => 'edit',
                'onclick' => 'importSkuList()',
            ),
        ),

        array(
            'text' => Yii::t('shopee', 'Batch Delete'),
            'url' => Yii::app()->createUrl('shopee/shopeeoffline/deleteExcludeList/'),
            'htmlOptions' => array(
                'class' => 'delete',
                'target' => 'selectedTodo',
                'rel' => 'shopee_exclude_widget',
                'postType' => 'string',
                'callback' => 'navTabAjaxDone',
                'title' => Yii::t('shopee', 'Are You Sure to Delete?'),
            ),
        ),

        array(
            'text' => Yii::t('shopee', 'Batch Disable'),
            'url' => Yii::app()->createUrl('shopee/shopeeoffline/updateExcludeStatus/', array('status'=> 0)),
            'htmlOptions' => array(
                'class' => 'edit',
                'target' => 'selectedTodo',
                'rel' => 'shopee_exclude_widget',
                'postType' => 'string',
                'callback' => 'navTabAjaxDone',
                'title' => Yii::t('shopee', 'Are You Sure to Disable?'),
            ),
        ),
        array(
            'text' => Yii::t('shopee', 'Batch Enable'),
            'url' => Yii::app()->createUrl('shopee/shopeeoffline/updateExcludeStatus/', array('status'=> 1)),
            'htmlOptions' => array(
                'class' => 'edit',
                'target' => 'selectedTodo',
                'rel' => 'shopee_exclude_widget',
                'postType' => 'string',
                'callback' => 'navTabAjaxDone',
                'title' => Yii::t('shopee', 'Are You Sure to Enable?'),
            ),
        ),
	),
	'pager'=>array(
		
	),
	'tableOptions'=>array(
		'layoutH'	=>	150,
		'tableFormOptions'	=>	true
	)
		
));
?>
<script>
    function importSkuList() {
        var url ='<?php echo Yii::app()->request->baseUrl;?>/shopee/shopeeoffline/showImportExcludeListForm/';
        $.pdialog.open(url, 'Import SKU List', '<?php echo Yii::t("shopee", "Import SKU List")?>', {width:800,
            height:600, mask:true});
        return true;
    }


</script>
