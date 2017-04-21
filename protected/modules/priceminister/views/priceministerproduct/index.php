<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'priceminister_product_add_list',
	'filter'	=>	$model,
	'dataProvider'	=>	$model->search(),
	'selectableRows'	=>	2,
	'columns'	=>	array(
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
							'name'=>'product_id',
							'value' => '$data->product_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					
					array(
							'name'=>'sku',
							'value' => '$data->sku',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'account_name',
							'value' => '$data->account_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'title',
							'value' => '$data->title',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:260px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'alias',
							'value' => '$data->alias',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:150px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'product_type',
							'value' => '$data->product_type',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'caption',
							'value' => '$data->caption',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'shipping_price',
							'value' => '$data->shipping_price.$data->shipping_price_currency',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					 array(
			            'name' => 'variant_id',
			            'value' => array($this, 'renderGridCell'),
			            'type' => 'raw',
			            'htmlOptions' => array(
			                'name' => 'pm_varant_ids',
			                'style' => 'width:56px',
			                'type' => 'checkbox',
			                //'click_event' => 'checkEbaySubSelect(this)',
			            ),
			        ),
					array(
							'name'=>'son_sku',
							'value'=>array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
								'style'=>'width:80px',
								'align'=>'center'
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),
					array(
							'name'=>'sale_price',
							'value'=>array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
									'style'=>'width:80px',
									'align'=>'center'
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),
					array(
							'name'=>'inventory',
							'value'=>array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
									'style'=>'width:50px',
									'align'=>'center'
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),
					array(
							'name'=>'advert_id',
							'value'=>array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
									'style'=>'width:80px',
									'align'=>'center'
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),
					
					array(
							'name'=>'create_time',
							'value' => '$data->create_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					
				),
	'toolBar'	=>	array(
		 array(
            'text' => Yii::t('priceminister', '批量更新库存'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'priceminister_product_add_list',
                'onclick' => 'batchUpdateStock()',
            )
        ),
		 array(
            'text' => Yii::t('priceminister', '批量更新价格'),
            'url' => "javascript:void(0)",
            'htmlOptions' => array(
                'class' => 'add',
                'rel' => 'priceminister_product_add_list',
                'onclick' => 'batchUpdatePrice()',
            )
        ),
		
			
	),
	'pager' => array(),
	'tableOptions' => array(
			'layoutH' => 150,
			'tableFormOptions' => true,
	),
));
?>
<script>
//批量更新库存
function batchUpdateStock() {
    var url = '/priceminister/priceministerproduct/batchUpdateStock';
    var ids = "";
    var arrChk = $("input[name='pm_varant_ids[]']:checked");
    if (arrChk.length == 0) {
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i = 0; i < arrChk.length; i++) {
        ids += arrChk[i].value + ',';
    }
    ids = ids.substring(0, ids.lastIndexOf(','));
    url += '/ids/' + ids;
    $.pdialog.open(url, 'batchUpdateStock', '批量更新库存', {width: 400, height: 200});
    return false;
}
//批量更新价格
function batchUpdatePrice() {
    var url = '/priceminister/priceministerproduct/batchUpdatePrice';
    var ids = "";
    var arrChk = $("input[name='pm_varant_ids[]']:checked");
    if (arrChk.length == 0) {
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i = 0; i < arrChk.length; i++) {
        ids += arrChk[i].value + ',';
    }
    ids = ids.substring(0, ids.lastIndexOf(','));
    url += '/ids/' + ids;
    $.pdialog.open(url, 'batchUpdatePrice', '批量更新价格', {width: 400, height: 200});
    return false;
}
</script>