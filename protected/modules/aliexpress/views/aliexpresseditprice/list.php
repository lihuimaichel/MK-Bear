<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'editprice-grid',
	'filter' => $model,
	'dataProvider' => $model->search(),
// 	'selectableRows' => 2,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->id',
			'selectableRows' => 2,
			'htmlOptions' => array(
				'style' => 'width:25px;',
			),
			'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:25px;'
			),
			'checkBoxHtmlOptions' => array(
				'onchange' => 'checkSelect(this)',
				'onpropertychange' => 'checkSelect(this)',
				'oninput' => 'checkSelect(this)',
			),
		),
		array(
			'name' => Yii::t('system', 'id'),
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:28px',
			),
		),
		array(
			'name' => 'sku',
			'type' => 'raw',
			'value' => '$data->sku',
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:70px;height:auto',
			),
		),
		array(
			'name' => 'product_price',
			'type' => 'raw',
			'value' => '$data->product_price',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:75px;height:auto',
			),
		),
		array(
			'name' => 'account_id',
			'type' => 'raw',
			'value' => '$data->account_id',
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:75px;height:auto',
			),
		),
		array(
			'name' => 'opration',
			'type' => 'raw',
			'value' => '$data->opration',
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:75px;height:auto',
			),
		),
	),
	'toolBar' => array(
		array (
			'text' => Yii::t ( 'system', '按折扣批量更改价格' ),
			'url'  => '/aliexpress/aliexpresseditprice/setdiscountbatcheditprice/discount/'.rtrim($discount,'%'),
			'htmlOptions' => array (
					'class' => 'delete',
					'target' => 'selectedToDo',
					'rel' => 'editprice-grid',
					'title' => '确认要按折扣批量更改吗?',
					'postType' => 'string',
					'callback' => '',
					'height' => '',
					'width' => ''
			)
		),
	),
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),		
));
?>
<script type="text/javascript">

function check(obj){ 
	$(obj).parent().find('.'+$(obj).val()).click();
}

// function saveChangePrice(val,id) {
// 	if(val && sku) {
// 		var url='/aliexpress/aliexpresseditprice/changeprice/id/'+id+'/val/'+val;
//		$.pdialog.open(url, 'res_content', '<?php// echo Yii::t('lazada_product', 'Change Price');?>', {width: 600, height: 350, mask:true});
// 	}
// }



</script>