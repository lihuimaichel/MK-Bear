<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget('UGridView', array(
	'id' => 'aliexpress_product_list',
	'filter' => $model,
	'dataProvider' => $model->search(),
	'selectableRows' => 2,
	'columns' => array(
		array(
			'class' => 'CCheckBoxColumn',
			'value' => '$data->id',
			'selectableRows' => 2,
			'htmlOptions' => array(
				'style' => 'width:20px;',
			),
			'headerHtmlOptions' => array(
				'align' => 'center',
				'style' => 'width:20px;',
				'onclick'=>'allSelectAliexpress(this)'
			),
			'checkBoxHtmlOptions' => array(
				'onchange' => 'checkSelect(this)',
				'onpropertychange' => 'checkSelect(this)',
				'oninput' => 'checkSelect(this)',
				'name'	=>	'aliexpress_product_ids[]',
			),
		),
		array(
			'name' => 'listing_id',
			'value' => '$row+1',
			'headerHtmlOptions' => array(
				'align' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:40px',
			),
		),
		array(
				'name' => 'account_name',
				'type' => 'raw',
				'value' => '$data->account_name',
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:80px',
						'align'	=>	'center'
				),
		),
		array(
			'name' => 'sku',
			'type' => 'raw',
			'value' => 'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
			'headerHtmlOptions' => array(
				'class' => 'center',
			),
			'htmlOptions' => array(
				'style' => 'width:80px',
				'align'	=>	'center'
			),
		),
                array(
                                'name' => 'id',
                                'type' => 'raw',
                                'value' => array($this, 'renderGridCell'),
                                'headerHtmlOptions' => array(
                                        'class' => 'center',
                                ),
                                'htmlOptions' => array(
                                        'style' => 'width:25px;height:auto',
                                        'type' => 'checkbox',
                                        'click_event'=>'checkSubSelect(this)',
                                        'name'	=>	'aliexpress_product_vids'
                                ),
		),
		array(
				'name' => 'sku_code',
				'type' => 'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:80px;height:auto',
				),
		),
		array(
				'name'	=>	'subject',
				'type'	=>	'raw',
				'value' => '$data->subject',
				'value' => 'CHtml::link($data->subject,"/aliexpress/aliexpressproduct/revisesubject/productId/".$data->aliexpress_product_id,
                array("subject"=>$data->subject,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>200))',
				'headerHtmlOptions'	=>	array(
						'class' => 'center',
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:200px;'
				)
		),
		array(
				'name'	=>	'product_category_path',
				'type'	=>	'raw',
				'value' => '$data->product_category_path',
				'headerHtmlOptions'	=>	array(
						'class' => 'center',
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:200px;'
				)
		),		
                array(
				'name'	=>	'system_available',
				'type'	=>	'raw',
				'value' =>      array($this, 'renderGridCell'),
				'headerHtmlOptions'=>array(
						'align'	=>	'center'
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:80px'
				)
		),
        array(
				'name' => 'ipm_sku_stock',
				'type' => 'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:50px;height:auto',
				),
		),
		array(
				'name' => 'sku_price',
				'type' => 'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:50px;height:auto',
				),
		),
		array(
				'name' => 'commission_rate',
				'type' => 'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:50px;height:auto',
				),
		),
		array(
				'name'	=>	'product_id',
				'type'	=>	'raw',
				'value' => '$data->itemurl',
				'headerHtmlOptions'	=>	array(
						'class' => 'center',
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:80px;'
				)
		),
		array(
				'name'	=>	'gmt_create',
				'type'	=>	'raw',
				'value' => '$data->gmt_create',
				'headerHtmlOptions'=>array(
						'align'	=>	'center'
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:80px'
				)
		),
		/*
		array(
				'name'	=>	'no_auto_offline_time',
				'type'	=>	'raw',
				'value' => '$data->no_auto_offline_time',
				'headerHtmlOptions'=>array(
						'align'	=>	'center'
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:80px'
				)
		),	
		*/	
		array(
				'name'	=>	'status_text',
				'type'	=>	'raw',
				'value' => '$data->status_text',
				'headerHtmlOptions'	=>	array(
						'align'=>'center',
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:50px'
				)
		),
                array(
				'name'	=>	'modify_stock',
				'type'	=>	'raw',
				'value' => array($this, 'renderGridCell'),
				'headerHtmlOptions'	=>	array(
						'align'=>'center',
				),
				'htmlOptions'	=>	array(
						'style'	=>	'width:150px'
				)
		),
			
			array(
					'name'	=>	'seller_name',
					'type'	=>	'raw',
					'value' => array($this, 'renderGridCell'),
					'headerHtmlOptions'	=>	array(
							'align'=>'center',
					),
					'htmlOptions'	=>	array(
							'style'	=>	'width:150px'
					)
			),
		array(
				'name' => 'offline',
				'type' => 'raw',
				'value' => '$data->offline',
				'headerHtmlOptions' => array(
						'class' => 'center',
				),
				'htmlOptions' => array(
						'style' => 'width:100px;height:auto',
				),
		),
		
	),
	'toolBar' => array(
		array(
			'text' => Yii::t('aliexpress_product', 'Batch Offline'),
			'url' => 'javascript:void(0)',
			'htmlOptions' => array(
					'class' => 'delete',
					'onclick' => 'batchOffline()',
				),
		),	
		array(
				'text' => Yii::t('aliexpress_product', 'Batch OnSelling'),
				'url' => 'javascript:void(0)',
				'htmlOptions' => array(
						'class' => 'delete',
						'onclick' => 'batchOffline("on")',
				),
		),
		array(
				'text' => Yii::t('aliexpress_product', 'Batch Offline Import'),
				'url' => '/aliexpress/aliexpressproduct/offlineimport',
				'htmlOptions' => array(
						'class' 	=> 'add',
						'target' 	=> 'dialog',
						//'rel' 		=> 'aliexpressparamtemplate-grid',
						'mask'  	=> true,
						'width' 	=> '800',
						'height' 	=> '600',
						'onclick'   => false
				)
		),
		array (
				'text' => '复制刊登',
				'url' => 'javascript:void(0)',
				'htmlOptions' => array (
						'class' => 'add',
						'rel' => 'aliexpress_product_list',
						'onclick' => 'copylisting()'
				)
		),
		array(
				'text' => Yii::t('aliexpress_product', 'Batch Change Stock'),
				'url' => 'javascript:void(0)',
				'htmlOptions' => array(
						'class' => 'delete',
						'onclick' => 'batchChangeStock()',
				),
		),
		array(
				'text' => Yii::t('aliexpress_product', 'Batch Modify Freight Template'),
				'url' => 'javascript:void(0)',
				'htmlOptions' => array(
						'class' => 'delete',
						'onclick' => 'batchModifyFreightTemplate()',
				),
		),
		array (
				'text' => '修改描述',
				'url' => 'javascript:void(0)',
				'htmlOptions' => array (
						'class' => 'add',
						'rel' => 'aliexpress_product_list',
						'onclick' => 'reviseDetail()'
				)
		),
		array (
				'text' => '更新附图',
				'url' => 'javascript:void(0)',
				'htmlOptions' => array (
						'class' => 'add',
						'rel' => 'aliexpress_product_list',
						'onclick' => 'batchUpdateFtImage()'
				)
		),
		array (
				'text' => '查询导出Excel',
				'url' => 'javascript:void(0)',
				'htmlOptions' => array (
						'class' => 'add',
						'rel' => 'aliexpress_product_list',
						'onclick' => 'batchDownloadExcelBySearchCondition()'
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
$(document).ready(function(){
	$("#sku_price_0").parent('.h25').css({'width':'210px'});
});

function allSelectAliexpress(obj){
	var chcked = !!$(obj).find("input").attr("checked");
	$("input[name='aliexpress_product_vids[]']").not(":disabled").each(function(){
		this.checked = chcked;
	});
}

function checkSelect(obj) {
	if (!!$(obj).attr('checked')) {
		$(obj).parents('tr').find("input[name='aliexpress_product_vids[]']").each(function(){
			this.checked = true;
		});
	} else {
		$(obj).parents('tr').find("input[name='aliexpress_product_vids[]']").each(function(){
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
		if (t == 'onselling'){
			url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/onselling/')?>';
			confirmMsg = '<?php echo Yii::t('aliexpress_product', 'Really want to onSelling these product?');?>';
		} else if(t == 'offline'){
			url = "<?php echo Yii::app()->createUrl('aliexpress/aliexpressproduct/offline');?>";
			confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
		} else if(t == 'modifyOnline'){
			url = "/aliexpress/aliexpressproductadd/updateonline/id/" + $(obj).attr('productid') + '/type/' + $(obj).attr('publishtype');
			//location.href=url;
			var htmlTemp = '';
			//htmlTemp    += "<a href='" + url + "' target='navTab' id='modifyOnlineJS' rel='modifyOnlineJS'>1111111</a>";
			htmlTemp    += "<a href=\"" + url + "\" target=\"navTab\" id=\"ajun_" + $(obj).attr('productid') + "_0\" rel=\"Ajun" + $(obj).attr('productid') + "0\">修改</a>";
			$("#ajun_" + $(obj).attr('productid') + "_0").show();
			$("#ajun_" + $(obj).attr('productid') + "_0").click();
			$("#ajun_" + $(obj).attr('productid') + "_0").hide();
			return false;
		} else {
			return false;
		}
		if(t != 'modifyOnline' && confirm(confirmMsg)){
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
			noChkedMsg = "<?php echo Yii::t('aliexpress_product', 'Not Specify Sku Which Need To Active');?>";
			url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/batchonselling/')?>';
			confirmMsg = '<?php echo Yii::t('aliexpress_product', 'Really want to onSelling these product?');?>';
		}else{
			noChkedMsg = "<?php echo Yii::t('aliexpress_product', 'Not Specify Sku Which Need To Inactive');?>";
			url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/batchoffline/')?>';
			confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
		}
		//检测
		var chkednum = 1*$("input[name='aliexpress_product_ids\[\]']:checked").length;
		if(chkednum<=0 || chkednum==undefined){
			alertMsg.error(noChkedMsg);
			return false;
		}
		/*进行确认操作*/
		if(confirm(confirmMsg)){
			postData = $("input[name='aliexpress_product_ids[]']:checked").serialize();
			$.post(url, postData, function(data){
				if (data.statusCode == '200') {
					alertMsg.correct(data.message);				
				} else {
					alertMsg.error(data.message);
				}
			}, 'json');
		}
	}
	/**
	 * 批量删除
	 * 暂时不需要
	 */
	function batchDelete(){
		return false;
		//检测
		var chkednum = 1*$("input[name='aliexpress_product_ids[]']:checked").length;
		if(chkednum<=0 || chkednum==undefined){
			alertMsg.error('<?php echo Yii::t('aliexpress_product', 'Not Specify Sku Which Need To Delete');?>');
			return false;
		}
		/*进行确认操作*/
		if(confirm('<?php echo Yii::t('system', 'Really want to offline these product?');?>')){
			postData = $("input[name='aliexpress_product_ids[]']:checked").serialize();
			var url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/batchdelete/')?>';
			$.post(url, postData, function(data){
				if (data.statusCode == '200') {
					alertMsg.correct(data.message);				
				} else {
					alertMsg.error(data.message);
				}
			}, 'json');
		}
	}

	//置零子sku库存
	function changeVariation(vid){
		if(confirm('<?php echo Yii::t('system', '确定要更改该子SKU ?');?>')){
			var stock = $("#variation_"+vid).val();
			if(stock == '' || stock < 0 || stock>9999){
				alertMsg.error('库存范围0-9999');
				return false;
			}
			var url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/changevariationstock')?>';
			var postData = {'vid':vid, 'stock':stock};
			$.post(url, postData, function(data){
				if (data.statusCode == '200') {
					alertMsg.correct(data.message);				
				} else {
					alertMsg.error(data.message);
				}
			}, 'json');
		}
	}
	//恢复子sku库存
	function batchChangeStock(){
		//确定是否选择了
		var stock = $("#modify_stock").val();
		if(stock == '' || stock < 0 || stock>9999){
			alertMsg.error('库存范围0-9999');
			return false;
		}
		//检测
		var chkednum = 1*$("input[name='aliexpress_product_vids\[\]']:checked").length;
		var noChkedMsg = '还没有选择子sku';
		if(chkednum<=0 || chkednum==undefined){
			alertMsg.error(noChkedMsg);
			return false;
		}
		if(confirm('确定要修改子sku库存为'+stock+'?')){
			var vid = new Array();
			$("input[name='aliexpress_product_vids\[\]']:checked").each(function(i, n){
				vid[i] = $(n).val();
			});
			var vids = vid.join(',');
			var url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/changevariationstock')?>';
			var postData = {'vid':vids, 'stock':stock};
			$.post(url, postData, function(data){
				if (data.statusCode == '200') {
					alertMsg.correct(data.message);				
				} else {
					alertMsg.error(data.message);
				}
			}, 'json');
		}
	}

	//批量修改运费模板
	function batchModifyFreightTemplate(){
		//获取账号
		var accountId = "<?php echo $accountId; ?>";
		if(!accountId){
			alertMsg.error('请选择账号');
			return false;
		}

		var ids = "";
	    var arrChk= $("input[name='aliexpress_product_ids[]']:checked");
	    if(arrChk.length > 0){
	        for (var i=0;i<arrChk.length;i++)
		    {
		        ids += arrChk[i].value+',';
		    }
	    }
	 
		var url ='/aliexpress/aliexpressproduct/batchmodifyfreighttemplate';
	    var param = {'accountId':accountId, 'ids':ids};
		$.pdialog.open(url, 'batchModifyFreightTemplate', '按账号批量修改运费模板', {width:600, height:200});
		$.pdialog.reload(url,{data:param})
	}

	//复制刊登
	function copylisting(){
		var ids = "";
	    var arrChk= $("input[name='aliexpress_product_ids[]']:checked");
	    if(arrChk.length==0){
	        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
	        return false;
	    }
	    for (var i=0;i<arrChk.length;i++)
	    {
	        ids += arrChk[i].value+',';
	    }
	    ids = ids.substring(0,ids.lastIndexOf(','));
	    var url ='/aliexpress/aliexpressproduct/copylisting/ids/'+ids;
		$.pdialog.open(url, 'copylisting', '复制刊登', {width:980, height:400});
	    return false;
	}

	//修改描述
	function reviseDetail(){
		var id = "";
	    var arrChk= $("input[name='aliexpress_product_ids[]']:checked");
	    if(arrChk.length==0){
	        alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
	        return false;
	    }
	    
	    if(arrChk.length > 1){
	        alertMsg.error('只能选择一个');
	        return false;
	    }

	    for (var i=0;i<arrChk.length;i++)
	    {
	        id += arrChk[i].value;
	    }

	    var url ='/aliexpress/aliexpressproduct/revisedetail/id/'+id;
		$.pdialog.open(url, 'revisedetail', '修改描述', {width:1100, height:600});
	    return false;
	}

	//批量更新图片
	function batchUpdateFtImage(){
		//确定是否选择了
		var chkednum = 1*$("input[name='aliexpress_product_ids\[\]']:checked").length;
		var noChkedMsg = '还没有选择子sku';
		if(chkednum<=0 || chkednum==undefined){
			alertMsg.error(noChkedMsg);
			return false;
		}

		var id = new Array();
		$("input[name='aliexpress_product_ids\[\]']:checked").each(function(i, n){
			id[i] = $(n).val();
		});
		var ids = id.join(',');
		var url = '<?php echo Yii::app()->createUrl('/aliexpress/aliexpressproduct/addupdateftimages')?>';
		var postData = {'ids':ids};
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
	
		//批量导出制定查询条件数据
	function batchDownloadExcelBySearchCondition(){
		var url  = '/aliexpress/aliexpressproduct/downloadExcel';
		var AccountID = $("#account_id").val();
		var Status    = $('#product_status_type').val();
		var GmtStart  = $('#gmt_create_0').val();
		var GmtEnd    = $('#gmt_create_1').val();
		if (AccountID == '' && (GmtStart == '' && GmtEnd == '') && Status == ''){
			alertMsg.error('请在账号、状态、时间区间中至少选择一个条件！');
			return false;
		}
		if (AccountID != ''){
			url += '/a/' + AccountID;
		}
		
		if (Status != ''){
			url += '/b/' + Status;
		}
		
		if (GmtStart != ''){
			if (GmtEnd == ''){
				alertMsg.error('请选择结束时间！');
				return false;
			}
			url += '/c/' + GmtStart;
		}
		if (GmtEnd != ''){
			if (GmtStart == ''){
				alertMsg.error('请选择开始时间！');
				return false;				
			}
			url += '/d/' + GmtEnd;
		}		
		
		window.open(url,'_blank');
	}
	
	//获取分类id
	function modifyProductCategory(){
		
		var url ='/aliexpress/aliexpresscategoryforproductlist/index';
		$.pdialog.open(url, 'modifyProductCategory', '选择产品分类', {width:1800, height:400});
		console.log($(window).width());
		if ($(window).width() < 1588){
			$($("body").data('modifyProductCategory')).css('margin-left','288px');
		}		
	}
	
	//选择分类id
	function addCategoryID(productCategoryID){
		var listCategoryValue = $('#category_id_JS');		
		$.pdialog.close('modifyProductCategory');
		listCategoryValue.val(productCategoryID);
		console.log(productCategoryID);
	}



	
</script>