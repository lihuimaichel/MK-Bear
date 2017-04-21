<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'wish_local_zero_widget_grid',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
			array(
					'class'=>'CCheckBoxColumn',
					'value'=>'$data->id',
					'selectableRows' => 2,
					'htmlOptions'=>array(
						'style'=>'width:20px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'allSelectWish(this)',
					),
					'checkBoxHtmlOptions'=>array(
						'onchange'=>'checkSelect(this)',
						//'onclick'=>'checkSelect(this)',
						'oninput'=>'checkSelect(this)',
						'onpropertychange'=>'checkSelect(this)'
					)
			),
			array(
					'name'=>'account_name',
					'value'=>'$data->account_name',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:60px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'product_id',
					'value'=>'$data->product_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'parent_sys_sku',
					'value'=>'CHtml::link($data->parent_sys_sku,"/products/product/viewskuattribute/sku/".$data->parent_sys_sku,
    				array("title"=>$data->parent_sys_sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),

			array(
					'name'=>'parent_sku',
					'value'=>'$data->parent_sku',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:180px;word-wrap:break-word;word-break:break-all;'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'online_sku',
					'value'=>'$data->online_sku',
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:180px;word-wrap:break-word;word-break:break-all;',
						'align'=>'center'
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
						'style'=>'width:100px',
						'align'=>'center'
					),
					'headerHtmlOptions'=>array(
						'align'=>'center'
					),
			),
			array(
					'name' => 'update_user_id',
					'value' => '$data->update_user_id',
					'type'  => 'raw',
					'htmlOptions'=>array(
						'style'=>'width:80px',
						'align'=>'center'
					),
					'headerHtmlOptions'=>array(
						'align'=>'center'
					),
			),

			array(
				'name' => 'status',
				'value' => '$data->status',
				'type'  => 'raw',
				'htmlOptions'=>array(
					'style'=>'width:100px',
					'align'=>'center'
				),
				'headerHtmlOptions'=>array(
					'align'=>'center'
				),
			),

			array(
				'name' => 'remark',
				'value' => '$data->remark',
				'type'  => 'raw',
				'htmlOptions'=>array(
					'style'=>'width:300px',
					'align'=>'center'
				),
				'headerHtmlOptions'=>array(
					'align'=>'center'
				),
			),
			array(
					'name'=>'create_time',
					'value'=>'$data->create_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:160px;'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:100px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/wish/wishlocalzerostock/updateStatus", array("id" => $data->id))',
									'label'     => Yii::t('wish_listing', '修改'),
									'options'   => array(
											'target'    => 'dialog',
											'class'     => 'btnEdit',
											'rel' 		=> 'wish_local_zero_widget_grid',
											'postType' 	=> '',
											'callback' 	=> '',
											'height' 	=> '300',
											'width' 	=> '500'
									),
							),
					),
			),
			
	),
	'toolBar'=>array(
			
			/*array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => Yii::app()->createUrl('/wish/wishlisting/batchdelete'),
					'htmlOptions' 	=> array(
							'class' 	=> 'delete',
							'title'		=>	Yii::t('system', 'Really want to delete these records?'),
							'target' 	=> 'selectedTodo',
							'mask'		=>true,
							'rel' 		=> 'wish_listing_widget',
							'width' 	=> '',
							'height' 	=> '',
							'onclick' 	=> '',
							'postType' => 'string',
							'warn' => Yii::t ( 'system', 'Please Select' ),
							'callback' => 'navTabAjaxDone'
					)
			),*/
			array (
					'text' => Yii::t ( 'system', '批量处理' ),
					'url' => '/wish/wishlocalzerostock/batchupdate',
					'htmlOptions' => array (
							'class' => 'delete',
							'target' => 'selectedToDo',
							'rel' => 'wish_local_zero_widget_grid',
							'title'	=>	'确定执行已断货操作？',
							'postType' => 'string',
							'callback' => '',
							'height' => '',
							'width' => ''
					)
			),
			array (
					'text' => Yii::t ( 'system', '批量导出' ),
					'url'  => 'javascript:void(0)',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => '',
							'rel' => '',
							'onclick' => 'localzeroDownCSV()'
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

function localzeroDownCSV(){
	var request = "<?php echo $request; ?>";
	var exportUrl ='/wish/wishlocalzerostock/exportlocalzero?' + request;
	$.ajax({
		type: 'get',
		url	: '/wish/wishlocalzerostock/checklocalzero?' + request,
		data: {},
		success:function(result){
			if(result.statusCode != '200'){
				alertMsg.error(result.message);
			}else{
				window.location.href=exportUrl;
			}
		},
		dataType:'json'
	});
}

</script>
