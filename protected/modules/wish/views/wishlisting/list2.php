<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'wish_listing_widget',
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
						'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
					'checkBoxHtmlOptions'=>array(
						'onchange'=>'checkSelect(this)',
						//'onclick'=>'checkSelect(this)',
						'oninput'=>'checkSelect(this)',
						'onpropertychange'=>'checkSelect(this)'
					)
			),
			array(
					'name'=>'sku',
					'value'=>'$data->sku',
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
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'name',
					'value'=>'$data->name',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:300px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'num_sold',
					'value'=>'$data->num_sold_total',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'num_saves',
					'value'=>'$data->num_saves_total',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			/* array(
					'name'=>'review_status_text',
					'value'=>'$data->review_status_text',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:200px'
					),
			), */
			array(
					'name'=>'variants_id',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						'name'=>'wish_varants_ids',
						'style'=>'width:20px',
						'type'=>'checkbox',
						'click_event'=>'checkSubSelect(this)',
						'disabled'=>'return $v["enabled"];'
					),
					
			),
			array(
					'name'=>'account_name',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'online_sku',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						'style'=>'width:200px',
						'align'=>'center'
					),
					'headerHtmlOptions'=>array(
						'align'=>'center'
					),
			),
			/* array(
					'name'=>'sku',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
						//'style'=>'width:100px',
						'align'=>'center'
					)
			), */
			array(
					'name'=>'sale_property',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:150px',
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
							'style'=>'width:100px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'price',
					'value'=>array($this, 'renderGridCell'),
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
					'name'=>'shipping',
					'value'=>array($this, 'renderGridCell'),
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
					'name'=>'msrp',
					'value'=>array($this, 'renderGridCell'),
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
					'name'=>'staus_text',
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
					'name'=>'oprator',
					'value'=>array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px',
							'align'=>'center'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			)
	),
	'toolBar'=>array(
			array(
					'text' => Yii::t('wish_listing', 'Batch Offline'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array(
							'class' => 'delete',
							'onclick' => 'batchOffline()',
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


<script type="text/javascript">
function checkSelect(obj) {
	console.log(obj);
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='wish_varants_ids[]']").not(":disabled").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='wish_varants_ids[]']").not(":disabled").each(function(){
			this.checked = false;
		});
	}
}

function checkSubSelect(obj){
	var curstatus = !!$(obj).attr("checked");
	//查找当前级别下所有的checkbox的选中情况
	var l = $(obj).closest("table").find("tr input:checked").length;
	var parenObj = $(obj).closest("table").closest("tr").find("input:first");
	if(l>0){
		parenObj.attr("checked", true);
	}else{
		parenObj.attr("checked", false);
	}
}

/**
 * 下线
 */
function offLine(obj, id){
	var confirmMsg = '', url = '', t;
	t = $(obj).val();
	if(t == 'online'){
		url = '<?php echo Yii::app()->createUrl('wish/wishlisting/online/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else if(t == 'offline'){
		url = "<?php echo Yii::app()->createUrl('wish/wishlisting/offline');?>";
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}else{
		return false;
	}
	if(confirm(confirmMsg)){
		var param = {id:id};
		$.post(url, param, function(data){
				if(data.statusCode == '200'){
					alertMsg.correct(data.message, data);	
				} else {
					alertMsg.error(data.message, data);
				}
		},'json');
	}
	return false;
}

/**
 * 批量下线
 */
function batchOffline(t){
	var noChkedMsg = confirmMsg = '', url = '';
	if(t == 'on'){
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Active');?>";
		url = '<?php echo Yii::app()->createUrl('/wish/wishlisting/batchonselling/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else{
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
		url = '<?php echo Yii::app()->createUrl('/wish/wishlisting/batchoffline/')?>';
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}
	//检测
	var chkednum = 1*$("input[name='wish_varants_ids\[\]']:checked").length;
	if(chkednum<=0 || chkednum==undefined){
		alertMsg.error(noChkedMsg);
		return false;
	}
	/*进行确认操作*/
	if(confirm(confirmMsg)){
		postData = $("input[name='wish_varants_ids[]']:checked").serialize();
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
}
</script>