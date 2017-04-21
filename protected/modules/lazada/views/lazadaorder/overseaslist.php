<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'overseasorder-grid',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'name'=>'order_id',
					'value'=>'$data->order_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:230px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'platform_order_id',
					'value'=>'$data->platform_order_id',
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:220px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'total_price',
					'value'=>'$data->total_price',
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
					'name'=>'ori_create_time',
					'value'=>'$data->ori_create_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:180px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'sku',
					'value'=>'$data->sku',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:110px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'sale_price',
					'value'=>'$data->sale_price',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'quantity',
					'value'=>'$data->quantity',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'warehouse_name',
					'value'=>'$data->warehouse_name',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:140px'
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
	),
	'toolBar'=>array(
		array (
			'text' => Yii::t ( 'system', '批量导出' ),
			'url'  => 'javascript:void(0)',
			'htmlOptions' => array (
					'class' => 'add',
					'target' => '',
					'rel' => '',
					'onclick' => 'overseaOrderDownCSV()'
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
function overseaOrderDownCSV(){
    var request = "<?php echo $request; ?>";
    var url ='/lazada/lazadaorder/overseaswarehouseorderxls?' + request;
	window.open(url);
}
</script>