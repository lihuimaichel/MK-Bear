<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'amazon_product_list',
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
						'headerHtmlOptions' => array(
								'align' => 'center',
								'style' => 'width:20px;',
								'onclick'=>'allSelectAmazon(this)'
						),
						'checkBoxHtmlOptions' => array(
								'onchange' => 'checkSelect(this)',
								'onpropertychange' => 'checkSelect(this)',
								'oninput' => 'checkSelect(this)',
						),
							
					),
					array(
							'name'=>'sku',
							'value' => 'CHtml::link($data->sku,"/products/product/viewskuattribute/sku/".$data->sku,
    				array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'id',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:25px;',
									'type' => 'checkbox',
									'click_event'=>'checkSubSelect(this)',
									'name'	=>	'amazon_product_ids',
									'disabled'=>'return $v["quantity"] > 0 ? true : false;'
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'seller_sku',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:130px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
										
							),
								
					),	
					array(
							'name'=>'asin1',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),					
					array(
							'name'=>'product_img',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
		                        'style'=>'width:80px',
		                        'onmouseover'=> 'showLarge(this)',
		                        'onmouseout'=>  'hideLarge(this)',
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),									
					array(
							'name'=>'account_name',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
							
						),																		
					array(
							'name'=>'seller_name',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),		
					array(
							'name'=>'seller_status_text',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),						
					array(
							'name'=>'title',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:350px;',
										
							),
							'headerHtmlOptions' => array(
									'style' => 'width:350px;',
							),
					
					),
					array(
							'name'=>'price',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
					
					),
					array(
							'name'=>'product_cost',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
								'style' => 'width:60px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
								
					),
					array(
							'name'=>'product_weight',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
								'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
								
					),										
					array(
							'name'=>'quantity',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
								'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
								
					),							
					array(
							'name'=>'available_qty',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
								'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
								
					),
					array(
							'name'=>'send_warehouse',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions'=>array(
									'style'=>'width:75px'
							),
							'headerHtmlOptions'=>array(
									'align'=>'center'
							),
					),																												
					array(
							'name'=>'amazon_listing_id',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
									
							),
								
					),					
					array(
							'name'=>'product_id',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'open_date',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:80px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),							
					),
					array(
							'name'=>'currency_code',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),							
					),																			
					array(
							'name'=>'fulfillment_type_text',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),					
					array(
							'name'=>'opreator',
							'value' => array($this, 'renderGridCell'),
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
				),
	'toolBar'	=>	array(
		
			array(
					'text' => Yii::t('aliexpress_product', 'Batch Offline'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array(
							'class' => 'delete',
							'onclick' => 'batchOffline()',
					),
			),
			/* array(
					'text' => Yii::t('aliexpress_product', 'Batch OnSelling'),
					'url' => 'javascript:void(0)',
					'htmlOptions' => array(
							'class' => 'delete',
							'onclick' => 'batchOffline("on")',
					),
			), */
			array(
			 		'text' => Yii::t('amazon_product', 'Import CSV offline'),
					'url' => Yii::app()->createUrl('/amazon/amazonproduct/importcsvoffline'),
					'htmlOptions' 	=> array(
							'class' 	=> 'add',
							'target' 	=> 'dialog',
							'mask'		=>true,
							'rel' 		=> 'amazon_product_list',
							'width' 	=> '900',
							'height' 	=> '600',
							'onclick' 	=> '',
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
		url = '<?php echo Yii::app()->createUrl('/amazon/amazonproduct/batchonselling/')?>';
		confirmMsg = '<?php echo Yii::t('amazon_product', 'Really want to onSelling these product?');?>';
	}else{
		noChkedMsg = "<?php echo Yii::t('amazon_product', 'Not Specify Sku Which Need To Inactive');?>";
		url = '<?php echo Yii::app()->createUrl('/amazon/amazonproduct/batchoffline/')?>';
		confirmMsg = '<?php echo Yii::t('system', 'Really want to offline these product?');?>';
	}
	//检测
	var chkednum = 1*$("input[name='amazon_product_ids\[\]']:checked").length;
	if(chkednum<=0 || chkednum==undefined){
		alertMsg.error(noChkedMsg);
		return false;
	}
	/*进行确认操作*/
	if(confirm(confirmMsg)){
		postData = $("input[name='amazon_product_ids[]']:checked").serialize();
		$.post(url, postData, function(data){
			if (data.statusCode == '200') {
				alertMsg.correct(data.message);				
			} else {
				alertMsg.error(data.message);
			}
		}, 'json');
	}
}

	//显示大图
    function showLarge(obj)
    {
		var height       = '450px';
		var width        = '450px';
		var parentHeight = $(obj).parent().parent().height();
		var top          = $(obj).parent().parent().position().top;
       // $(obj).parent('tr').css('position', 'relative').css('overflow', 'initial');
        console.log(top);
        console.log(parentHeight+top);
        var imgObj = $(obj).find('img')[0];
        var child = $('<div>').css('position', 'absolute')
            .css('height', height)
            .css('width', width)
            .css('top', parentHeight- (parentHeight+top)+'px')
            .css('left', '450px')
            .addClass('large-image').append($('<img>').css('width', width ).css('height', height).attr('src', imgObj.src));
        $(obj).append(child);

    }
    function hideLarge(obj) {
        //return true;
        $(obj).find("div.large-image").each(function (){
            $(this).remove();
        });
    }

</script>