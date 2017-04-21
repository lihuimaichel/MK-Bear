<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'wish_listing_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'name'=>'ID',
					'value'=>'$data->id',
					
					'htmlOptions'=>array(
						'style'=>'width:100px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'',
					),
					
			),
			array(
					'name'=>'platform_order_id',
					'value'=>'$data->platform_order_id',
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'order_co_id',
					'value'=>'$data->order_co_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:250px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'price',
					'value'=>'$data->price',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'currency',
					'value'=>'$data->currency',
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
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'order_status',
					'value'=>'$data->order_status',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'updated_at',
					'value'=>'$data->updated_at',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),			
	),
	'toolBar'=>array(
		array (
			'text' => Yii::t ( 'system', 'LAZADA批量导出' ),
			'url'  => 'javascript:void(0)',
			'htmlOptions' => array (
					'class' => 'add',
					'target' => '',
					'rel' => '',
					'onclick' => 'orderDownCSV()'
			)
		),
		array (
			'text' => Yii::t ( 'system', 'SHOPEE批量导出' ),
			'url'  => 'javascript:void(0)',
			'htmlOptions' => array (
					'class' => 'add',
					'target' => '',
					'rel' => '',
					'onclick' => 'shopeeOrderDownCSV()'
			)
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
<script type="text/javascript">

function orderDownCSV(){
    var request = "<?php echo $request; ?>";
    var url ='/lazada/lazadaorder/cashondeliveryorderxls?' + request;
    var ajaxurl ='/lazada/lazadaorder/cashondeliveryorderxlsajax?' + request;
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

function shopeeOrderDownCSV(){
    var request = "<?php echo $request; ?>";
    var url ='/shopee/shopeeorder/allorders?' + request;
	window.open(url);
}
</script>