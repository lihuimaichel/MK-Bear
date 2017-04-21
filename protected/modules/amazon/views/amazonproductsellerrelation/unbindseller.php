<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'amazonproductunbindsellerrelation-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', 'Add' ),
					'url' => '/amazon/amazonproductsellerrelation/import',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'amazonproductunbindsellerrelation-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
			array (
					'text' => Yii::t ( 'system', '设置账号销售'),
					'url' => '/amazon/amazonproductsellerrelation/batchchangeunbindtoseller',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'dialog',
							'rel' => 'amazonproductunbindsellerrelation-grid',
							'postType' => '',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
			
			array (
					'text' => Yii::t ( 'system', '批量SKU设置'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array (
							'class' => 'add',
							'rel' => 'amazonproductunbindsellerrelation-grid',
							'onclick' => 'showChangeUnbindSkuSeller()'
					)
			),	


			array (
					'text' => Yii::t ( 'system', '批量导出'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => '',
							'rel' => '',
							'onclick' => 'unbindSkuSellerDownCSV()',
					)
			),		
		
	),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'	=> '$data->id',
						'htmlOptions'=>array(
								'style' => 'width:60px;'
						)
				),
				
			
				array(
						'name' => 'Product ID',
						'value' => '$data->item_id',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:180px;'),
				),
	
             	array(
						'name'  => 'seller_sku',
						'value' => '$data->seller_sku',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:160px;',
						),
				),
				

			

				array(
						'name'  => 'account_id',
						'value' => '$data->account_name',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:90px;',
						),
				),
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
);

$this->widget('UGridView', $options);

?>

<script type="text/javascript">
function showChangeUnbindSkuSeller(){

    var ids = "";
    var arrChk= $("input[name='amazonproductunbindsellerrelation-grid_c0[]']:checked");
    if(arrChk.length==0){
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i=0;i<arrChk.length;i++)
    {
        ids += arrChk[i].value+',';
    }

    var url ='/amazon/amazonproductsellerrelation/batchchangeunbindskutoseller';
    var param = {'ids':ids};
	$.pdialog.open(url, 'showChangeUnbindSkuSeller', '批量设置SKU', {width:600, height:400});
	$.pdialog.reload(url,{data:param})

    return false;
}

function unbindSkuSellerDownCSV(){
    var request = "<?php echo $request; ?>";
    var url ='/amazon/amazonproductsellerrelation/unbindsellerexportxls?' + request;
    var ajaxurl ='/amazon/amazonproductsellerrelation/unbindsellerexportxlsajax?' + request;
    var checkType = 0;

    htmlobj=$.ajax({url:ajaxurl,async:false});
    checkType = parseInt(htmlobj.responseText);
	if(checkType == 1){
		window.open(url);
	}else{
		alertMsg.error('无数据');
		return;
	}
	
}

</script>