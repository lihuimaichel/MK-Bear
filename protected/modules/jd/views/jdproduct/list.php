<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'jd_listing_widget',
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
					'name'=>'title',
					'value'=>'$data->title',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'supply_price',
					'value'=>'$data->supply_price',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'ware_id',
					'value'=>'$data->ware_id',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'item_num',
					'value'=>'$data->item_num',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'account_name',
					'value'=>'$data->account_name',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'ware_status',
					'value'=>'$data->ware_status_text',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'sub_sku',
					'value'=> array($this, 'renderGridCell'),
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
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'supply_price',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'amount_count',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'is_stock',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'sale_stock_amount',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'lock_count',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'lock_start_time',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			array(
					'name'=>'lock_end_time',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			
			
			
			array(
					'name'=>'oprator',
					'value'=>'$data->oprator',
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
			/* array(
					'text' => Yii::t('wish_listing', 'Batch Offline'),
					'url' => Yii::app()->createUrl('jd/jdproduct/batchoffline'),
					'htmlOptions' => array(
							'class' => 'delete',
							'target'=> 'selectedTodo',
							'rel'	=>	'jd_listing_widget',
							'title' =>	Yii::t('system', 'Really want to offline these product?'),
							'postType'=>'string',
							'callback'  => 'navTabAjaxDone',
							'onclick' => '',
					),
			), */
		
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

function allSelectWish(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='jd_varants_ids[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}
function checkSelect(obj) {
	console.log(obj);
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='jd_varants_ids[]']").not(":disabled").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='jd_varants_ids[]']").not(":disabled").each(function(){
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
		url = '<?php echo Yii::app()->createUrl('jd/jdproduct/online')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else if(t == 'offline'){
		url = "<?php echo Yii::app()->createUrl('jd/jdproduct/offline');?>";
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
		url = '<?php echo Yii::app()->createUrl('jd/jdproduct/batchonline')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else{
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
		url = '<?php echo Yii::app()->createUrl('jd/jdproduct/batchoffline')?>';
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}
	//检测
	var chkednum = 1*$("input[name='jd_varants_ids\[\]']:checked").length;
	if(chkednum<=0 || chkednum==undefined){
		alertMsg.error(noChkedMsg);
		return false;
	}
	/*进行确认操作*/
	if(confirm(confirmMsg)){
		postData = $("input[name='jd_varants_ids[]']:checked").serialize();
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