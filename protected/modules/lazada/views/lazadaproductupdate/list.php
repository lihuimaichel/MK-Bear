<script type="text/javascript">
function getAccountList(obj) {
	var siteID = $(obj).val();
	var url = 'lazada/lazadaaccount/getaccountlist/site_id/' + siteID;
	$.get(url, function(data) {
		$('#account_id').html(data);
	}, 'html')
}
</script>
<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'lazadaproductupdate-grid',
	'filter' => $model,
	'dataProvider' => $model->search(),
// 	'selectableRows' => 2,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->seller_sku',
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
			'name' => 'seller_sku',
			'type' => 'raw',
			'value' => '$data->seller_sku',
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:70px;height:auto',
			),
		),
		array(
			'name' => 'price',
			'type' => 'raw',
			'value' => '$data->price',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:75px;height:auto',
			),
		),
		array(
			'name' => 'changeway',
			'type' => 'raw',
			'value' => '$data->changeway',
			'headerHtmlOptions' => array(
					'class' => 'center',
			),
			'htmlOptions' => array(
					'style' => 'width:75px;height:auto',
			),
		),
// 		array(
// 			'name' => 'opration',
// 			'type' => 'raw',
// 			'value'	=> '$data->opration',
// 			'headerHtmlOptions' => array(
// 					'class' => 'center',
// 			),
// 			'htmlOptions' => array(
// 					'style' => 'width:100px;height:auto',
// 			),
// 		)
	),
	'toolBar' => array(),
	'pager' => array(),
	'tableOptions' => array(
		'layoutH' => 150,
		'tableFormOptions' => true
	),		
));
?>
<script type="text/javascript">

function saveChangePrice(val,id) {
	if(val && id) {
		var url='/lazada/lazadaproductupdate/changeprice/id/'+id+'/val/'+val;
		$.pdialog.open(url, 'res_content', '<?php echo Yii::t('lazada_product', 'Change Price');?>', {width: 600, height: 350, mask:true});
	}
}



</script>