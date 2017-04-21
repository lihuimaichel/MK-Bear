<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'=>'paytm_product_listing_widget',
	'filter'=>$model,
	'dataProvider'=>$model->search(),
	'selectableRows' => 2,
	'columns'=>array(
    	    array(
    	        'class' => 'CCheckBoxColumn',
    	        'value' => '$data->product_id',
    	        'selectableRows' => 2,
    	        'htmlOptions' => array(
    	            'style' => 'width:20px;',
    	        ),
    	        'headerHtmlOptions' => array(
    	            'align'   => 'center',
    	            'style'   => 'width:20px;',
    	            'onclick' => 'allSelectPayTm($(this))',    	            
    	        ),
    	        'checkBoxHtmlOptions' => array(
    	            'onchange' => 'checkSelect($(this))',
    	            'onpropertychange' => 'checkSelect($(this))',
    	            'oninput' => 'checkSelect($(this))',
    	            'name'	=>	'paytm_product_ids[]',
    	            
    	        ),
    	    ),	    

			array(
					'name'=>'product_id',
					'value'=>'$data->product_id',
					
					'htmlOptions'=>array(
						'style'=>'width:66px',
						'align'=>'center',
					),
					'headerHtmlOptions'=>array(
							'align'=>'center',
							'onclick'=>'',
					),
					
			),
	    
			array(
					'name'=>'account_name',
					'value'=>'isset($data->account_id) ? $data->account_id : ""',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:66px'
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
							'style'=>'width:100px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			
			array(
					'name'=>'paytm_sku',
					'value'=>'$data->paytm_sku',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:88px'
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
							'style'=>'width:88px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
	    
            array(
					'name'=>'pay_money_type',
					'value'=>'$data->pay_money_type',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:28px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'creat_time',
					'value'=>'$data->creat_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:66px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'update_time',
					'value'=>'$data->update_time',
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:66px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
	        //id,product_id,price,sku,paytm_sku,creat_time,update_time
    	    array(
    	        'name'=>'child_product_id',
    	        'value'=> array($this, 'renderGridCell'),
    	        'type'=>'raw',
    	        'headerHtmlOptions' => array(
    	            'class' => 'center',
    	        ),
    	        'htmlOptions' => array(
    	            'style' => 'width:25px;height:auto',
    	            'type'  => 'checkbox',
    	            'click_event' =>  'checkSubSelect($(this))',
    	            'name'	      =>  'paytm_product_child_id'
    	        ),
    	    ),	        
			array(
					'name'=>'child_sku',
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
					'name'=>'child_paytm_sku',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:288px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'child_price',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:66px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'  => 'child_inventory',
					'value' => array($this, 'renderGridCell'),
					'type'  => 'raw',
					'htmlOptions'=>array(
						'style'=>'width:66px'
					),
					'headerHtmlOptions' => array(
						'align'=>'center'
					),
			),
	        //id,product_id,price,sku,paytm_sku,creat_time,update_time
			array(
					'name'=>'child_modify_price',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:88px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'child_modify_stock',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:88px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
    	    array(
    	        'name'=>'status',
    	        'value'=> array($this, 'renderGridCell'),
    	        'type'=>'raw',
    	        'htmlOptions'=>array(
    	            'style'=>'width:66px'
    	        ),
    	        'headerHtmlOptions'=>array(
    	            'align'=>'center'
    	        ),
    	    ),	    
			array(
					'name'=>'setting',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:168px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'child_creat_time',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:168px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
			array(
					'name'=>'child_update_time',
					'value'=> array($this, 'renderGridCell'),
					'type'=>'raw',
					'htmlOptions'=>array(
							'style'=>'width:168px'
					),
					'headerHtmlOptions'=>array(
							'align'=>'center'
					),
			),
	    
			
	),
	'toolBar'=>array(
	    array(
	        'text' => '批量下架',
	        'url' => 'javascript:void(0)',
	        'htmlOptions' => array(
	            'class' => 'delete',
	            'onclick' => 'batchOffline()',
	        ),
	    ),
	    array(
	        'text' => '批量上架',
	        'url' => 'javascript:void(0)',
	        'htmlOptions' => array(
	            'class' => 'add',
	            'onclick' => 'batchOnline()',
	        ),
	    ),
	    array(
	        'text' => '批量修改库存',
	        'url' => 'javascript:void(0)',
	        'htmlOptions' => array(
	            'class' => 'add',
	            'onclick' => 'batchChangeStock()',
	        ),
	    ),
	    array(
	        'text' => '批量修改价格',
	        'url' => 'javascript:void(0)',
	        'htmlOptions' => array(
	            'class' => 'add',
	            'onclick' => 'batchChangePrice()',
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
<script>
	
	function modifyChildStatus(productID,type){
		if(confirm('确认修改产品状态？')){
			$.ajax({
				type: 'post',
				url: '/paytm/paytmproduct/ajax',
				data:{
					productIDPost:productID,
					typePost:type
				},
				success:function(result){
					console.log(result);
					if (result != null && typeof result == 'object'){
						if (result.status == 'failure'){
							alertMsg.error(result.msg);
						} else if (result.status == 'success'){
							alertMsg.correct(result.msg);
							$('.pagination').find("li[class^='selected']").find('a').click();
						}
					} else {
						$('#errorBox').html(result).fadeIn(666);
					}
				},			
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					 //alert(XMLHttpRequest.status);
					console.log(XMLHttpRequest.status);
					 if (XMLHttpRequest.status == 500){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 } else if (XMLHttpRequest.status == 404){
						 alertMsg.error('请求的页面不存在，404错误！');
					 } else if (XMLHttpRequest.status == 200){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 }
				},		
				dataType:'json'
			});
		}		
	}
	
	function modifyChildStock(productID){
		if(confirm('确认修改产品库存？')){
			var stockValue = $('#stock_value_' + productID).val();
			if ($.trim(stockValue) == ''){
				alertMsg.error('请填写具体的库存！');
				return false;
			}	
			$.ajax({
				type: 'post',
				url: '/paytm/paytmproduct/ajax',
				data:{
					productIDPost:productID,
					stockValuePost:stockValue,
					typePost:'modifyChildStock'
				},
				success:function(result){
					console.log(result);
					if (result != null && typeof result == 'object'){
						if (result.status == 'failure'){
							alertMsg.error(result.msg);
						} else if (result.status == 'success'){
							alertMsg.correct(result.msg);
							$('.pagination').find("li[class^='selected']").find('a').click();
						}
					} else {
						$('#errorBox').html(result).fadeIn(666);
					}
				},			
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					 //alert(XMLHttpRequest.status);
					console.log(XMLHttpRequest.status);
					 if (XMLHttpRequest.status == 500){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 } else if (XMLHttpRequest.status == 404){
						 alertMsg.error('请求的页面不存在，404错误！');
					 } else if (XMLHttpRequest.status == 200){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 }
				},		
				dataType:'json'
			});
		}		
	}
	
	function modifyChildPrice(productID){
		if(confirm('确认修改产品价格？')){
			var priceValue = $('#price_value_' + productID).val();
			if ($.trim(priceValue) == ''){
				alertMsg.error('请填写具体的价格！');
				return false;
			}
			$.ajax({
				type: 'post',
				url: '/paytm/paytmproduct/ajax',
				data:{
					productIDPost:productID,
					priceValuePost:priceValue,
					typePost:'modifyChildPrice'
				},
				success:function(result){
					console.log(result);
					if (result != null && typeof result == 'object'){
						if (result.status == 'failure'){
							alertMsg.error(result.msg);
						} else if (result.status == 'success'){
							alertMsg.correct(result.msg);
							$('.pagination').find("li[class^='selected']").find('a').click();
						}
					} else {
						$('#errorBox').html(result).fadeIn(666);
					}
				},			
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					 //alert(XMLHttpRequest.status);
					console.log(XMLHttpRequest.status);
					 if (XMLHttpRequest.status == 500){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 } else if (XMLHttpRequest.status == 404){
						 alertMsg.error('请求的页面不存在，404错误！');
					 } else if (XMLHttpRequest.status == 200){
						 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
					 }
				},		
				dataType:'json'
			});
		}			
	}
	
	function checkSubSelect(Obj){
		var thisValueArr = Obj.val().split(',');
		var thisParentProductID = thisValueArr[0];
		if (Obj.is(':checked')){
			$("input[value='" + thisParentProductID + "']").prop('checked',true);	
		} else {
			var allNotSelect = true;
			Obj.parent().parent().parent().find('input').each(function(){
				if ($(this).is(':checked')){
					$("input[value='" + thisParentProductID + "']").prop('checked',true);
					allNotSelect = false;
					return false;
				} else {
					
				}
			});	
			allNotSelect && $("input[value='" + thisParentProductID + "']").prop('checked',false);
			$("th[onclick='allSelectPayTm($(this))']").find('input').prop('checked',false);
		}
		
	}
	
	function batchOffline(){
		if(confirm('确认批量下架产品？')){
			var batchOfflineJson = '';
			$("input[name^='paytm_product_child_id']").each(function(){
				if ($(this).is(':checked')){
					var thisValueArr = $(this).val().split(',');
					var thisProductID = thisValueArr[1];
					var thisAccountID = thisValueArr[2];	
					batchOfflineJson += thisProductID + ',' + thisAccountID + '=';	
				}
			});	
			console.log(batchOfflineJson);	
			if ($.trim(batchOfflineJson) == ''){
				alertMsg.error('请至少选择一个再提交');
			} else {
				$.ajax({
					type: 'post',
					url: '/paytm/paytmproduct/ajax',
					data:{
						batchOfflineJsonPost:batchOfflineJson,
						typePost:'batchOffline'
					},
					success:function(result){
						console.log(result);
						if (result != null && typeof result == 'object'){
							if (result.status == 'failure'){
								alertMsg.error(result.msg);
							} else if (result.status == 'success'){
								alertMsg.correct(result.msg);
								$('.pagination').find("li[class^='selected']").find('a').click();
							}
						} else {
							$('#errorBox').html(result).fadeIn(666);
						}
					},			
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						 //alert(XMLHttpRequest.status);
						console.log(XMLHttpRequest.status);
						 if (XMLHttpRequest.status == 500){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 } else if (XMLHttpRequest.status == 404){
							 alertMsg.error('请求的页面不存在，404错误！');
						 } else if (XMLHttpRequest.status == 200){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 }
					},		
					dataType:'json'
				});
			}
		}				
	}
	
	function batchOnline(){
		if(confirm('确认批量上架产品？')){
			var batchOnlineJson = '';	
			$("input[name^='paytm_product_child_id']").each(function(){
				if ($(this).is(':checked')){
					var thisValueArr = $(this).val().split(',');
					var thisProductID = thisValueArr[1];
					var thisAccountID = thisValueArr[2];	
					batchOnlineJson += thisProductID + ',' + thisAccountID + '=';	
				}
			});
			console.log(batchOnlineJson);	
			if ($.trim(batchOnlineJson) == ''){
				alertMsg.error('请至少选择一个再提交');
			} else {
				$.ajax({
					type: 'post',
					url: '/paytm/paytmproduct/ajax',
					data:{
						batchOnlineJsonPost:batchOnlineJson,
						typePost:'batchOnline'
					},
					success:function(result){
						console.log(result);
						if (result != null && typeof result == 'object'){
							if (result.status == 'failure'){
								alertMsg.error(result.msg);
							} else if (result.status == 'success'){
								alertMsg.correct(result.msg);
								$('.pagination').find("li[class^='selected']").find('a').click();
							}
						} else {
							$('#errorBox').html(result).fadeIn(666);
						}
					},			
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						 //alert(XMLHttpRequest.status);
						console.log(XMLHttpRequest.status);
						 if (XMLHttpRequest.status == 500){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 } else if (XMLHttpRequest.status == 404){
							 alertMsg.error('请求的页面不存在，404错误！');
						 } else if (XMLHttpRequest.status == 200){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 }
					},		
					dataType:'json'
				});
			}
		}				
	}

	function batchChangeStock(){
		var allSameStock = null;
		if(confirm('确认批量修改产品库存？')){
			if (confirm('是否要把所有库存设置为一样？')){
				allSameStock = prompt('请输入要设为一样的库存数量！',999);							 		
			}
			//alert(allSameStock);
			var batchChangeStockJson = '';
			var errorMsg = '';
			$("input[name^='paytm_product_child_id']").each(function(){
				if ($(this).is(':checked')){
					var thisValueArr = $(this).val().split(',');
					var thisProductID = thisValueArr[1];
					var thisAccountID = thisValueArr[2];
					var stockValue = $('#stock_value_' + thisProductID).val();	
					if ($.trim(stockValue) == '' && allSameStock == null){
						errorMsg = '库存不能为空!';
						$('#stock_value_' + thisProductID).focus();
						return false;
					} else {
						if (allSameStock != null){
							batchChangeStockJson += thisProductID + ',' + allSameStock + ',' + thisAccountID + '=';
						} else {
							batchChangeStockJson += thisProductID + ',' + stockValue + ',' + thisAccountID + '=';
						}						
					}	
				}
			});	
			console.log(batchChangeStockJson);
			if (errorMsg != ''){
				alertMsg.error(errorMsg);
				return false;
			}
			if ($.trim(batchChangeStockJson) == ''){
				alertMsg.error('请至少选择一个并且填写完整再提交');
			} else {
				$.ajax({
					type: 'post',
					url: '/paytm/paytmproduct/ajax',
					data:{
						batchChangeStockJsonPost:batchChangeStockJson,
						typePost:'batchChangeStock'
					},
					success:function(result){
						console.log(result);
						if (result != null && typeof result == 'object'){
							if (result.status == 'failure'){
								alertMsg.error(result.msg);
							} else if (result.status == 'success'){
								alertMsg.correct(result.msg);
								$('.pagination').find("li[class^='selected']").find('a').click();
							}
						} else {
							$('#errorBox').html(result).fadeIn(666);
						}
					},			
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						 //alert(XMLHttpRequest.status);
						console.log(XMLHttpRequest.status);
						 if (XMLHttpRequest.status == 500){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 } else if (XMLHttpRequest.status == 404){
							 alertMsg.error('请求的页面不存在，404错误！');
						 } else if (XMLHttpRequest.status == 200){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 }
					},		
					dataType:'json'
				});
			}
		}		
	}
	
	function batchChangePrice(){
		if(confirm('确认批量修改产品价格？')){
			var batchChangePriceJson = '';
			var errorMsg = '';
			$("input[name^='paytm_product_child_id']").each(function(){
				if ($(this).is(':checked')){
					var thisValueArr = $(this).val().split(',');
					var thisProductID = thisValueArr[1];
					var thisAccountID = thisValueArr[2];
					var priceValue = $('#price_value_' + thisProductID).val();	
					if ($.trim(priceValue) == ''){
						errorMsg = '价格不能为空!';
						$('#price_value_' + thisProductID).focus();
						return false;
					} else {
						batchChangePriceJson += thisProductID + ',' + priceValue + ',' + thisAccountID + '=';
					}	
				}
			});	
			console.log(batchChangePriceJson);	
			if (errorMsg != ''){
				alertMsg.error(errorMsg);
				return false;
			}
			if ($.trim(batchChangePriceJson) == ''){
				alertMsg.error('请至少选择一个并且填写完整再提交');
			} else {
				$.ajax({
					type: 'post',
					url: '/paytm/paytmproduct/ajax',
					data:{
						batchChangePriceJsonPost:batchChangePriceJson,
						typePost:'batchChangePrice'
					},
					success:function(result){
						console.log(result);
						if (result != null && typeof result == 'object'){
							if (result.status == 'failure'){
								alertMsg.error(result.msg);
							} else if (result.status == 'success'){
								alertMsg.correct(result.msg);
								$('.pagination').find("li[class^='selected']").find('a').click();
							}
						} else {
							$('#errorBox').html(result).fadeIn(666);
						}
					},			
					error: function(XMLHttpRequest, textStatus, errorThrown) {
						 //alert(XMLHttpRequest.status);
						console.log(XMLHttpRequest.status);
						 if (XMLHttpRequest.status == 500){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 } else if (XMLHttpRequest.status == 404){
							 alertMsg.error('请求的页面不存在，404错误！');
						 } else if (XMLHttpRequest.status == 200){
							 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
						 }
					},		
					dataType:'json'
				});
			}
		}				
	}
	
	function allSelectPayTm(Obj){
		if (Obj.find('input').is(':checked')){
			$("input[name^='paytm_product_ids']").prop('checked',true);
			$("input[name^='paytm_product_child_id']").prop('checked',true);
		} else {
			$("input[name^='paytm_product_ids[]']").prop('checked',false);
			$("input[name^='paytm_product_child_id[]']").prop('checked',false);
		}		
	}
	
	function checkSelect(Obj){
		if (Obj.is(':checked')){
			$("input[value^='" + Obj.val() + ",']").prop('checked',true);
		} else {
			$("input[value^='" + Obj.val() + ",']").prop('checked',false);
		}		
	}

    jQuery(document).on('mouseover','.innerTable tr',function() {
        var trThis = $(this);
		$(this).css('background','#ededed');
		$(this).attr('who','this');
		$(this).siblings().css('background','#ffffff');
		$(this).parent().find('tr').each(function(index,dom){
			//console.log(index);
			var nowIndex = index;
			if ($(this).attr('who') == 'this'){
				trThis.parent().parent().parent().siblings().find('.innerTable').each(function(indexSiblings,domSiblings){
					//console.log('indexSiblings:'+indexSiblings);
					$(this).find('tr').each(function(indexTr,domTr){
						if (indexTr == nowIndex){
							$(this).css('background','#ededed');
							$(this).attr('who','this');
							$(this).siblings().css('background','#ffffff');
							return false;
						}
					});
				});
				return false;
			}
		});
    });
    
    jQuery(document).on('mouseout','.innerTable tr',function() {
        $('.innerTable tr').each(function() {
        	$(this).css('background','#ffffff');
    		$(this).attr('who','');
        });		
    });
    
    jQuery(document).ready(function() {
        $('#allPagesSelected').parent().remove();
    });

</script>
<div style="position:absolute; top:18%; left:8%; width:68%; height:58%; background:#f6f6f6; z-index:666666; display:none; padding:28px; font-size:18px; line-height:28px;" 
     id="errorBox" 
     onClick="$(this).html(''); $(this).fadeOut(666);"
><div>













