<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_batch_change_log_list',
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
							
					),
					array(
							'name'=>'item_id',
							'value' => '$data->item_id_link',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'type',
							'value' => '$data->type_text',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'status',
							'value' => '$data->status_text',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:300px;',
					
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
						'name'=>'last_msg',
						'value' => '$data->last_msg',
						'type'=>'raw',
						'htmlOptions' => array(
								'style' => 'width:100px;',
									
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
						),
				
				),
			
				array(
						'name'=>'reason',
						'value' => '$data->reason',
						'type'=>'raw',
						'htmlOptions' => array(
								'style' => 'width:100px;',
									
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
						),
				
				),
			
				array(
						'name'=>'create_user_id',
						'value' => 'MHelper::getUsername($data->create_user_id)',
						'type'=>'raw',
						'htmlOptions' => array(
								'style' => 'width:100px;',
									
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
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
				array(
						'name'=>'update_time',
						'value' => '$data->update_time',
						'type'=>'raw',
						'htmlOptions' => array(
								'style' => 'width:100px;',
									
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
						),
				
				),
					array(
							'header' => Yii::t('system', 'Operation'),
							'class' => 'CButtonColumn',
							'template' => '{update1}',
							'htmlOptions' => array(
									'style' => 'text-align:center;width:160px;',
							),
							'buttons' => array(
									
					
									'update1' => array(
											'url'       => 'Yii::app()->createUrl("/ebay/ebayproductbatchchangelog/changenow", array("id" => $data->id))',
											'label'     => Yii::t('aliexpress', 'Upload Now'),
											'options'   => array(
													'title'     => Yii::t('aliexpress', 'Are you sure to upload these'),
													'target'    => 'ajaxTodo',
													'rel'       => 'ebay_product_batch_change_log_list',
													'postType'  => 'string',
													'callback'  => 'navTabAjaxDone',
													'onclick'	=>	'',
													'style'		=>	'width:80px;height:28px;line-height:28px;'
											),
											'visible'	=>	'$data->visiupload'
									),
							),
					),
				),
	'toolBar'	=>	array(
			array(
					'text' => Yii::t('ebay', 'Batch Upload'),
					'url' => Yii::app()->createUrl('ebay/ebayproductbatchchangelog/batchuploadupdatedesc'),
					'htmlOptions' => array (
							'class' => 'add',
							'title' => Yii::t ( 'ebay', 'Really want to upload these records?' ),
							'target' => 'selectedTodo',
							'rel' => 'ebay_product_batch_change_log_list',
							'postType' => 'string',
							'warn' => Yii::t ( 'system', 'Please Select' ),
							'callback' => 'navTabAjaxDone'
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


<script type="text/javascript">

$('#sub_sku_online').parent().css({"width":"280px"});	//固定宽度

function allSelectAmazon(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='amazon_product_ids[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}

function checkSelect(obj) {
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='amazon_product_ids[]']").not(":disabled").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='amazon_product_ids[]']").not(":disabled").each(function(){
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
		url = '<?php echo Yii::app()->createUrl('/amazon/amazonproduct/online/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else if(t == 'offline'){
		url = "<?php echo Yii::app()->createUrl('amazon/amazonproduct/offline');?>";
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
		url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchonselling/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else{
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
		url = '<?php echo Yii::app()->createUrl('/ebay/ebayproduct/batchoffline/')?>';
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}
	//检测
	var chkednum = 1*$("input[name='ebay_product_list_c0[]']:checked").length;
	if(chkednum<=0 || chkednum==undefined){
		alertMsg.error(noChkedMsg);
		return false;
	}
	/*进行确认操作*/
	if(confirm(confirmMsg)){
		postData = $("input[name='ebay_product_list_c0[]']:checked").serialize();
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
}


function batchChangeDescAndPicture(){
	
	var ids = "";
    var arrChk= $("input[name='ebay_product_list_c0[]']:checked");
    if(arrChk.length==0){
        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
        return false;
    }
    for (var i=0;i<arrChk.length;i++)
    {
        ids += arrChk[i].value+',';
    }

    var url ='/ebay/ebayproduct/batchupdatedesc';
    var param = {'ids':ids};
	$.pdialog.open(url, 'EbayBatchupdatedesc', '批量更新详情和图片', {width:600, height:400});
	$.pdialog.reload(url,{data:param})

    return false;
}
</script>