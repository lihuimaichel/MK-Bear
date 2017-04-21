<style>
<!-- 
.pageFormContent label{
	display: inline;
    float: none;
    width: auto;
}
#wish_product_update table td li{line-height:20px;}
#wish_product_update table td font.bold{font-weight:bold;}
#wish_product_update table.dataintable_inquire td td{border:none;}
#wish_product_update .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
/* #wish_product_update table{display:inline-block;} */
.categoryBox {
	margin:0;
	padding:0;
}
.categoryBox .tabBody, .categoryBox .tabHeader, .categoryBox .tabFooter {
	margin-bottom:10px;
}
ul.tabHeaderList {
	padding:0;
	margin:0;
	overflow:hidden;
	display:block;
	clear:both;
	border-bottom:1px #aaaaaa solid;
}
ul.tabHeaderList li {
	display:block;
	float:left;
	background-color:#c0c0c0;
	border:1px #c0c0c0 solid;
	margin-right:10px;
	border-radius:5px 5px 0 0;
}
ul.tabHeaderList li.on {
	background-color:#efefef;
	position:relative;
	bottom:-1px;
	z-index:1;
}
ul.tabHeaderList li a {
	padding:5px 15px;
	display:block;
	font-weight:bold;
	text-decoration:none;	
}
.categoryBox .tabContent {
	display:none;
	overflow:hidden;
	margin-bottom:10px;
}

.pageContent input.textInput {
	border:1px #bbbbbb solid;
	padding:2px 5px;
	line-height:27px;
}

.pageContent .attributesTable input.textInput {
	width:100px;
}

.categoryBox a.btn {
	display:block;
	float:left;
	margin-left:10px;
	border:1px #bbbbbb solid;
	background-color:#cccccc;
	line-height:22px;
	padding:5px 20px;
	text-decoration:none;
}
ul.attributeValueList {
	margin:0;
	padding:0;
	overflow:hidden;
}
ul.attributeValueList li {
	display:block;
	float:left;
	margin:0 5px 3px 0;
}
table.attributesTable{
	padding:0;
	margin:0;
	width:100%;
}
table.attributesTable td, table.baseinfoTable td{
	vertical-align:middle;
	padding:7px 5px;
}
table.attributesTable td.leftColumn {
	text-align:right;
	width:15%;
}
table.attributesTable td.rightColumn {
	text-align:left;
	width:85%;
}
.tabs .tabsHeader {
	background-position: 0 0;
    display: block;
    /* height: 28px; */
    overflow: hidden;
    padding-left: 5px;
	height:auto;
}
.tabs .tabsHeaderContent {
	background-position: 100% -50px;
    display: block;
    /* height: 28px; */
    overflow: hidden;
    padding-right: 5px;
	height:auto;
}
.tabs .tabsHeader ul {
	background-position: 0 -100px;
    background-repeat: repeat-x;
    display: block;
    /* height: 28px;	 */
	height:auto;
}
.tabs .tabsHeader li {
	background-position: 0 -250px;
    background-repeat: repeat-x;
    cursor: pointer;
    display: block;
    float: left;
   /*  height: 28px; */
    margin-right: 2px;	
	height:auto;
}
.tabs .tabsHeader li.selected span {
 	background-position: 100% -500px;
    color: red;
    font-weight: bold;	
}
a.moreBtn {
	color:blue;
}
div.keywordsRow {
/* 	margin:0 0 10px;
	overflow:hidden; */
}
.tabs .tabsHeader ul {
	overflow:hidden;
}
#wish_product_update table.dataintable_inquire table.variationProductTable {
	min-width:100px;
	padding:0;
	margin:10px auto;
	align:center;
	border-width:0 0 0 1px;
	border-color:#888888;
	border-style:solid;
}
#wish_product_update table.dataintable_inquire table.variationProductTable th, #wish_product_update table.dataintable_inquire table.variationProductTable td {
	padding:7px 25px;
	border-width:1px 1px 1px 0;
	border-color:#888888;
	border-style:solid;
}
ul.productSize {
	margin:0;
	padding:0;
	overflow:hidden;
}
ul.productSize li {
	float:left;
	margin:0 10px 0 0;
}
div.customAttributes {
	padding:10px;
	margin:10px;
	border-top:1px dashed #666666;
}
div.customAttributes a {
	color:#15428b;
	text-decoration:none;
}

div.ztimgs .extra_checked{
	display:none;
}

div.ftimgs .extra_checked{
	display:block;
}
-->
</style>
<script type="text/javascript">

var wish_product_update_func = {
		//检测字符长度
		checkStrLength : function(self, max){
			var length = $(self).val().length;
			var remain = parseInt(max) - parseInt(length);
			if( remain >= 0 ){
				$(self).next('span.warn').html(remain+' <?php echo Yii::t('common','Char Left')?>');
			}else{
				$(self).val($(self).val().substr(0,max))
			}
		},
		//自动填写其他账号对应字段
		autoFill : function(obj) {
			var text = $(obj).val();
			$('input[group=' + $(obj).attr('group') + ']').each(function(){
				$(this).val(text);
			});
		},
		//保存刊登数据
		saveInfo : function(){
			$("div.ztimgs .extra_checked").attr("checked", true);
			$.ajax({
					type: 'post',
					url: $('form#wish_product_update').attr('action'),
					data:$('form#wish_product_update').serialize(),
					success:function(result){
						if(result.statusCode != '200'){
							alertMsg.error(result.message);
						}else{
							$('form#wish_product_update a.display_list').click();
							navTab.closeTab(result.navTabId);
						}
					},
					dataType:'json'
			});
		},
		del_variant : function(self, addId, sku){
			var sublen = $("tr.wish_product_update_subskulist").length;
			if(sublen<=1){
				alertMsg.error("至少保留一条！");
				return false;
			}
			if(confirm('确定要删除？删除将不可恢复！')){
				$.ajax({
					type: 'post',
					url: '<?php echo Yii::app()->createUrl('wish/wishproductadd/delvariant');?>',
					data:{'add_id':addId, 'sku':sku},
					success:function(result){
						if(result.statusCode != '200'){
							alertMsg.error(result.message);
						}else{
							$(self).parent('td').parent('tr').remove();
						}
					},
					dataType:'json'
				});
			}
		},
		//切换TAB
		changeTab : function(obj) {
			var tabId;
			$(obj).removeClass('on');
			tabId = $(obj).attr('class');
			$('ul.tabHeaderList li').each(function(){
				if (obj == this)
					$(this).addClass('on');
				else
					$(this).removeClass('on');
			});
			$('.tabBody .tabContent').each(function(){
				if ($(this).attr('id') == tabId)
					$(this).show();
				else
					$(this).hide();
			});
		},
		//返回
		backToAddList : function(obj){
			var navTabId = $(obj).attr('rel');
			//console.log(navTabId);
			$("a.BackToList").click();
			navTab.closeTab(navTabId);
		},

		//自定义添加子SKU
		addSubSKUProduct : function(){
			var sku = $.trim($("#inputsku").val());
			if (sku.length == 0){
				alertMsg.error("输入的子SKU不能为空");
			}else{
				var repeat_flag = 0;
				$.each($('#skuAttributeTable tbody tr').find("input[name$='[sku]']"),function(i,item){
					var cur_sku = $.trim($(item).val());
					if (sku == cur_sku){
						alertMsg.error("不能重复子SKU");
						repeat_flag = 1;
						return false;
					}
				});
				if (repeat_flag == 0){
					//判断该SKU是否为单品或者为子SKU，否则提示错误，其次判断是否侵权、停售产品
					var data = 'sku=' + sku;
					$.ajax({
							type: 'post',
							url: '<?php echo Yii::app()->createUrl('wish/wishproductadd/validatesku');?>',
							data:data,
							success:function(result){
								if(result.statusCode != '200'){
									alertMsg.error(result.message);
								}else{
									var tab = $('#skuAttributeTable tbody tr').eq(-1).clone();
									//设置SKU值								
									tab.find("input[name='wish_add_selupload[]']").val(sku);
									tab.find("input[name$='[sku]']").val(sku);
									tab.find("[name='sku_value']").html(sku);

									tab.attr('id','attr_'+Math.ceil(Math.random()*10000));
									tab.find("span").remove();
									tab.find(".otherdel").attr("onclick","wish_product_update_func.deletetr(this)");
									tab.find(".upload_status_show").html("<font color='blue'>待上传</font>");

									//替换name值
									$.each(tab.find("input[name^='skuinfo']"),function(j,ele){
										var reg = new RegExp('\\[(.+?)\\]');
										var v = $(ele).attr("name").replace(reg,"[" +sku+ "]");
										$(ele).attr("name",v);
									});
									tab.appendTo('#skuAttributeTable');
								}
							},
							dataType:'json'
					});
				}
			}
		},		

		//删除本行
		deletetr : function(self){
			if ($(self).parent().parent().parent().parent().children().length == 1){
				alertMsg.error('最后一个子SKU不能删除，请先添加别的子SKU再删除');
			}else{
				$(self).parent().parent().parent().remove();
			}
		}		
				
};

$(function(){
	//修改价格,获取利润
	// $(".sale_price_info").die("change");
	$(".sale_price_info").live("change",function(){
		changePriceValue(this);
		// $(this).parent().find('.profit_info').html('');
	});	

	$(".ship_to_price_info").live("change",function(){
		var obj = $(this).parent().parent().find(".sale_price_info");
		changePriceValue(obj);
		// $(obj).parent().find('.profit_info').html('');
	});		

	$("select[name$='[warehouse_id]']").live("change",function(){
		$.each($('#skuAttributeTable tbody tr').find(".sale_price_info"),function(i,item){
			changePriceValue(this);
		});
	});

	$("#refreshBtn").click(function(){
		$.each($('#skuAttributeTable tbody tr').find(".sale_price_info"),function(i,item){
			changePriceValue(this);
		});
	});			

	function changePriceValue(obj){
		var saleprice = $.trim($(obj).val());
		var sku = $(obj).attr("sku");
		var shipping = $("input[name^='skuinfo["+sku+"][shipping]']").val();
		var shipwarehouseid = $("select[name$='[warehouse_id]'] option:selected").val();
		var account_id = $("input[name='cur_account_id']").val();
		
		if(saleprice=='' || saleprice==0){
			$(obj).parent().find('.profit_info').html('');
		}else{
			$(obj).parent().find('.profit_info').html('计算中...');
			$.ajax({
				type: "GET",
				url: "/wish/wishproductadd/getprofitinfo",
				data: "sku="+sku+"&ship_price="+shipping+"&sale_price="+saleprice+"&ship_wharehoust_id="+shipwarehouseid+"&account_id="+account_id,
				dataType:'json',
				success: function(result){
					if(result.statusCode == 200){
						var html = "<span style='color:red;'>利润:<b>"+result.data.profit+"</b>，<br />利润率:<b>"+result.data.profitRate+"</b></span>";
						$(obj).parent().find('.profit_info').html(html);
					}else{
						alert("利润情况加载失败,请重试!");
					}
				}
			});
		}
	}
});

</script>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
	    <div class="bg14 pdtb2 dot">
	         <strong>SKU：[<?php echo $listingProduct['sku'];?>]</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
	       <div class="row productAddInfo" style="width:90%;float:left;">
	       <?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'wish_product_update',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('wish/wishproductadd/saveinfo'), 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
        		<table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				        <!-- 刊登参数显示START -->
				    	<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Add Params');?></td>
				            <td>
				                <ul>
    				                <li><font class="bold">SKU：</font><?php echo $listingProduct['sku'];?></li> 
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font><?php echo $listingParam['listing_type']['text'];?></li>
				                </ul>
				                <input type="hidden" name="parent_sku" value="<?php echo $listingProduct['parentSku'];?>" />
				                <input type="hidden" name="sku" value="<?php echo $listingProduct['sku'];?>" />
				                <input type="hidden" name="publish_type" value="<?php echo $listingParam['listing_type']['id'];?>" />
				                <input type="hidden" name="product_is_multi" value="<?php echo $listingProduct['skuInfo']['product_is_multi'];?>" />
				                <input type="hidden" name="action" value="<?php echo $action;?>" />
				                <input type="hidden" name="save_type" value="<?php echo $saveType;?>"/>
				                <input type="hidden" name="add_id" value="<?php echo $addId;?>"/>
				                <input type="hidden" name="cur_account_id" value="<?php echo $cur_account_id;?>" />
				            </td>
				        </tr>
				        <!-- 刊登参数显示END -->
				        
                        <!-- 图片信息显示START -->
                        <tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Image Info');?></td>
				            <td>
				                <div class="page unitBox ztimgs">
				                    <div><strong><?php echo Yii::t('aliexpress_product', 'Main Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php $i=0;if(!empty($listingProduct['skuImg']['zt'])):?>
                                        <?php foreach($listingProduct['skuImg']['zt'] as $k=>$image):?>
                                        <?php if(!in_array($k, $listingProduct['selectedImg']['zt'])){
                                        		continue;
                                        }?>
                                        <div style="position:relative;" class="aliexpress_image">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[extra][<?php echo $k?>]" <?php if($i == 0):?>checked<?php endif;?>/>
                                        </div>
                                        <?php $i++; endforeach;?>
                                        <?php endif;?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                
                                
                                
                                <div class="page unitBox ftimgs">
                                    <div><strong><?php echo Yii::t('aliexpress_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        
                                        <?php $count = 0;?>
                                        <?php if(!empty($listingProduct['skuImg']['ft'])):?>
                                        <?php foreach($listingProduct['skuImg']['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" class="extra_checked" <?php if(in_array($k, $listingProduct['selectedImg']['ft'])):?>checked <?php endif;?> value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[extra][<?php echo $k?>]" />
                                        </div>
                                        <?php $count++;?>
                                        <?php endforeach;?>
                                        <?php endif;?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                
				            </td>
				        </tr>
                        <!-- 图片信息显示END -->
                        				        

				        
                        <!-- sku属性显示START -->
                        <tr id="skuAttrRow">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Sale Attribute');?></td>
				            <td>
				                <div id="skuAttributes">
				               
				                <table id="skuAttributeTable" class="attributesTable">
				                	<thead>
				                		<tr>
				                			<th><?php echo Yii::t('wish_listing', 'Sku');?></th>
				                			<?php if ($attributeList): ?>
				                			<?php foreach ($attributeList as $attribute):?>
				                			<th><?php echo $attribute['attribute_name'];?></th>
				                			<?php endforeach;?>
				                			<?php endif;?>
				                			<th><?php echo Yii::t('wish_listing', 'Inventory');?></th>
				                			<th><?php echo Yii::t('wish_listing', 'Price');?></th>
				                			<th><?php echo Yii::t('wish_listing', 'Market Recommand Price');?></th>
				                			<th><?php echo Yii::t('wish_listing', 'Shipping');?></th>
				                			<th><?php echo Yii::t('wish_listing', 'Upload status');?></th>
				                			<th><?php echo Yii::t('system', 'Oprator');?></th>
				                		</tr>
				                	</thead>
									<tbody>
										<!--多属性-->
				                		<?php if($listingSubSKU):?>
				                		<?php foreach ($listingSubSKU as $val):?>
				                		
										<tr id="attr_<?php echo $val['product_id'];?>" class="wish_product_update_subskulist">
											<td>
												<div name="sku_value"><?php echo $val['sku'];?></div>
												<?php if( $val['skuInfo']['upload_status'] != 1):?>
												<input type="hidden" name="skuinfo[<?php echo $val['sku'];?>][sku]" value="<?php echo $val['sku'];?>"/>
												<?php endif;?>
											</td>
											<?php if ($attributeList): ?>
				                			<?php foreach ($attributeList as $attribute):?>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type='text' name='skuinfo[<?php echo $val['sku'];?>][<?php echo $attribute['attribute_name'];?>]' value="<?php if(isset($val['attribute'][$attribute['id']])){ echo $val['attribute'][$attribute['id']]['attribute_value_name']; } ?>">
				                			<?php else:?>
				                			<?php if(isset($val['attribute'][$attribute['id']])){ echo $val['attribute'][$attribute['id']]['attribute_value_name']; } ?>
				                			<?php endif;?>	
				                			</td>
				                			<?php endforeach;?>
				                			<?php endif;?>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][inventory]" value="<?php if(isset($val['skuInfo']['inventory'])) echo $val['skuInfo']['inventory']; else echo 1;?>" class="required"/>
				                			<?php else:?>
				                			<?php if(isset($val['skuInfo']['inventory'])) echo $val['skuInfo']['inventory']; else echo 1;?>
				                			<?php endif;?>
				                			</td>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][price]" value="<?php echo $val['skuInfo']['product_cost'];?>" class="required sale_price_info" sku="<?php echo $val['sku'];?>" />
				                				<span><?php echo isset($val['skuInfo']['price_error'])?$val['skuInfo']['price_error']:'';?></span>
				                				<span style="color:red;" class="profit_info"><?php if(isset($val['skuInfo']['price_profit'])) echo $val['skuInfo']['price_profit']; ?></span>
				                			<?php else:?>
				                				<?php echo $val['skuInfo']['product_cost'];?>
				                			<?php endif;?>
				                			</td>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][market_price]" value="<?php if(isset($val['skuInfo']['market_price'])) echo $val['skuInfo']['market_price']; else echo $val['skuInfo']['product_cost'];?>" class="required"/>
				                			<?php else:?>
				                				<?php if(isset($val['skuInfo']['market_price'])) echo $val['skuInfo']['market_price']; else echo $val['skuInfo']['market_price'];?>
				                			<?php endif;?>
				                			</td>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][shipping]" value="<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>" class="required ship_to_price_info" />
				                			<?php else:?>
				                			<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>
				                			<?php endif;?>
				                			</td>
				                			
				                			<td>
				                				<div class="upload_status_show">
				                					<?php echo $val['skuInfo']['upload_status_text'];?>
				                				</div>
				                			</td>
				                			<td>
				                				<div class="categoryBox" style="width:80px;">
					                				<?php if ($val['skuInfo']['upload_status'] != 1):?>
					                					<a href="javascript:;" class="btn otherdel" onclick="wish_product_update_func.del_variant(this, '<?php echo $addId;?>', '<?php echo $val['sku'];?>')"><?php echo Yii::t('system', 'Delete');?></a>
					                				<?php endif;?>
				                				</div>
				                			</td>
										</tr>
										<?php endforeach;?>
										<?php else:?>
										<!--单品-->	
										<tr id="attr_<?php echo $listingProduct['skuInfo']['id'];?>">
											<td>
												<?php echo $listingProduct['sku'];?>
												<input type="hidden" name="skuinfo[<?php echo $listingProduct['sku'];?>][sku]" value="<?php echo $listingProduct['sku'];?>" />
											</td>
											<?php if ($attributeList): ?>
				                			<?php foreach ($attributeList as $attribute):?>
				                			<td>
				                				<input type='text' name='skuinfo[<?php echo $listingProduct['sku'];?>][<?php echo $attribute['attribute_name'];?>]' value="<?php if(isset($listingProduct['attribute'][$attribute['id']])){ echo $listingProduct['attribute'][$attribute['id']]['attribute_value_name']; } ?>">
				                			</td>
				                			<?php endforeach;?>
				                			<?php endif;?>
				                			<td>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][inventory]" value="<?php echo $listingProduct['skuInfo']['inventory'];?>" class="required"/>
				                			</td>
				                			<td>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][price]" value="<?php echo $listingProduct['skuInfo']['product_cost'];?>" class="required sale_price_info" sku="<?php echo $listingProduct['sku'];?>" />
				                				<span><?php echo $listingProduct['skuInfo']['price_error'];?></span>
				                				<span style="color:red;" class="profit_info"><?php if(isset($listingProduct['skuInfo']['price_profit'])) echo $listingProduct['skuInfo']['price_profit']; ?></span>
				                			</td>
				                			<td>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][market_price]" value="<?php echo $listingProduct['skuInfo']['market_price'];?>" class="required"/>
				                			</td>
				                			<td>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][shipping]" value="<?php echo $listingProduct['skuInfo']['shipping'];?>" class="required ship_to_price_info" />
				                			</td>
				                			<td>
				                				<div class="upload_status_show">
				                					<?php echo $listingProduct['skuInfo']['upload_status_text'];?>
				                				</div>
				                			</td>
				                			<td>
				                				<div class="categoryBox" style="width:80px;">
					                				<?php if ($listingProduct['skuInfo']['upload_status'] != 1):?>
					                					<a href="javascript:;" class="btn otherdel" onclick="wish_product_update_func.del_variant(this, '<?php echo $addId;?>', '<?php echo $listingProduct['sku'];?>')"><?php echo Yii::t('system', 'Delete');?></a>
					                				<?php endif;?>
				                				</div>
				                			</td>
										</tr>
										<?php endif;?>
									</tbody>
								</table>
				              
				                </div>
				                <div id="productVariations">
				                </div>
				                <div id="addSubSKU" class="categoryBox" style="padding:10px 10px 45px 10px;">	
				                	<input type="text" id="inputsku" placeholder="输入子SKU" name="inputsku" />			                	
				                	<a href="#" onclick="wish_product_update_func.addSubSKUProduct()" class="btn" id="categoryConfirmBtn">添加子SKU</a>
				                	<a href="#" class="btn" id="refreshBtn">刷新利率</a>
				                </div>				                
				            </td>
				        </tr>
                        <!-- sku属性显示END -->
                        
            
                                              				        
				        <!-- 基本信息显示START -->
                        <tr>
				            <td rowspan="2" width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Base Info');?></td>
				            <td>
				                <div class="tabs"> 
	                                <div class="tabsHeader"> 
	 		                            <div class="tabsHeaderContent"> 
                            	 			<ul> 
                            	 			    <?php $k = 0;foreach($listingParam['listing_account'] as $accountID => $account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['account_name'];?>&nbsp;&nbsp;</span></a>
                            	 					<input type="hidden" name="account_id[]" value="<?php echo $accountID?>" />
                            	 				</li>
                            	 				<?php $k++;endforeach;?>
                            	 			</ul> 
                            	 		</div> 
                            	 	</div>
                            	 	<div class="tabsContent"> 
                            	 	    <?php foreach($listingParam['listing_account'] as $accountID=>$account):?>
                            	 	    <div class="pageFormContent" style="border:1px solid #B8D0D6">
                            	 	    <table class="baseinfoTable" width="98%">
                            	 			<tbody>
                            	 			<?php if($isSubSku):?>
                            	 			<tr>
                            	 				<td><span>提示：</span></td>
                            	 				<td><span style="color: red;">当前为子SKU，不能修改以下信息</span></td>
                            	 			</tr>
                            	 			<?php endif;?>
                             			    <tr>
                             			        <td style="width:80px">
                             			    		<span><?php echo Yii::t('aliexpress_product', 'Product Title');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<?php if(($listingProduct['skuInfo']['upload_status'] == 1)):?>
                             			    		<span><?php echo $account['title'];?></span>
                             			    		<?php else:?>
                             			    		<input type="text" class="required" name="baseinfo[<?php echo $accountID?>][subject]" value="<?php echo $account['title'];?>" onKeyDown = "wish_product_update_func.checkStrLength(this,300)" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    		<?php endif;?>
                             			    	</td>
                            	            </tr>
                            	            <tr>
                             			        <td>
                             			    		<span><?php echo Yii::t('wish_listing', 'Product Tags');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<?php if(!$account['tags']):?>
		                             			    	<?php if(($listingProduct['skuInfo']['upload_status'] == 1)):?>
	                             			    		-
	                             			    		<?php else:?>
		                             			    	<input class="required" type="text" name="baseinfo[<?php echo $accountID?>][tags][]" style="float:none;" value="" size="25"/>&nbsp;&nbsp;
		                             			    	<?php endif;?>
	                             			    	<?php else:?>
	                             			    	<?php foreach ($account['tags'] as $tag):?>
	                             			    	<?php if(($listingProduct['skuInfo']['upload_status'] == 1) ):?>
	                             			    		<span><?php echo $tag;?></span>
	                             			    	<?php else:?>
														<input class="required" type="text" name="baseinfo[<?php echo $accountID?>][tags][]" style="float:none;" value="<?php echo $tag;?>" size="25"/>&nbsp;&nbsp;
	                             			    	<?php endif;?>
	                             			    	<?php endforeach;?>
	                             			    	<?php if(!(($listingProduct['skuInfo']['upload_status'] == 1) )):?>
	                             			    	<?php endif;?>
	                             			    	<?php endif;?>
                             			    	</td> 
                            	            </tr>
                   	           				<tr>
                   	           					<td>
                             			    		<span><?php echo Yii::t('wish_listing', 'Brand Name');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<?php if(($listingProduct['skuInfo']['upload_status'] == 1) ):?>
                             			    		<span><?php echo $account['brand'];?></span>
                             			    		<?php else:?>
                             			    		<input  type="text" name="baseinfo[<?php echo $accountID?>][brand]" value="<?php echo $account['brand'];?>"/>
                             			    		<?php endif;?>
                             			    	</td>
                   	           				</tr>
                            	            
											<!-- 发货仓库 -->
                   	           				<tr>
                   	           					<td>
                             			    		<span><?php echo Yii::t('wish_listing', 'Send Warehouse');?>：</span>
                             			    	</td>
                             			    	<td>
													<select name="baseinfo[<?php echo $accountID?>][warehouse_id]" style="padding: 7px 0">
													<?php foreach($warehouseList as $k => $val): ?>
														<option value="<?php echo $k; ?>" <?php if($account['warehouse_id'] == $k){echo 'selected';}elseif($k == 41){echo 'selected';}?>><?php echo $val; ?></option>
													<?php endforeach; ?>	
													</select>                             			    		
                             			    	</td>
                   	           				</tr>                             	            
                            	            
					                        <!-- 产品描述START -->
					                        <tr>
					                        	<td><?php echo Yii::t('wish_listing', 'Product Description');?></td>
					                        	<td>
					                        		<textarea <?php if(($listingProduct['skuInfo']['upload_status'] == 1) ): echo "disabled"?><?php endif;?> style="width: 90%;height:300px;" name="baseinfo[<?php echo $accountID?>][detail]" class="productDescription required"><?php echo $account['description']?></textarea>
					                        	</td>
					                        </tr>
					                        <!-- 产品描述END -->                            	                                         	            
                            	 			</tbody>
                            	 		</table>
                             			</div>
                            	 		<?php endforeach;?>
                             	    </div>
                             	</div>                             	
				            </td>
				        </tr>
                        <tr>

                            <td>
                                <div><?php echo Yii::t('wish', 'Image URL will be uploaded')?></div>
                                <div>
                                    <?php echo \join('<br>', $remoteImages); ?>
                                </div>
                            </td>
                        </tr>
                       
                        <!-- 基本信息显示END -->
				    </tbody>
				</table>

				<div class="formBar">
                    <ul>
                    	<li>
                            <div class="buttonActive">
                                <div class="buttonContent"> 
                                    <a class="saveBtn"  onClick="wish_product_update_func.backToAddList(this);" href="javascript:void(0)" rel="<?php echo $currentNavTab;?>">
                                    	<?php echo Yii::t('wish_listing', 'Back To List');?>
                                    </a>&nbsp;
                                	<a href="<?php echo Yii::app()->createUrl('wish/wishproductaddlist/index');?>" rel="<?php echo 'page'.UebModel::model('Menu')->getIdByUrl('/wish/wishproductadd/index');?>" target="navTab" style="display:none;" class="BackToList">
                            			<?php echo Yii::t('wish_listing','Product Add List');?>
                            		</a>
                                </div>
                            </div>
                            
                        </li>
                        <?php if($saveType>0):?>
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent"> 
                                    <a class="saveBtn" onClick="wish_product_update_func.saveInfo();" href="javascript:void(0)"><?php echo Yii::t('lazada', 'Save Into List');?></a>&nbsp;
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('wish/wishproductaddlist/index');?>" target="navTab" style="display:none;" class="display_list">
                        	<?php echo Yii::t('wish_listing','Product Add List');?>
                        </a>
                        <?php endif;?>
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	
        	<div class="row imgArea" style="float:right;clear:none;width:9%;min-width:9%;">
        	   <table class="dataintable_inquire imgArea" width="100%" height="100%" cellspacing="1" cellpadding="3" border="0">
				    <tr>
				        <td>
				            <div>
    				            <div>
    				                <span style="display:block;"><?php echo Yii::t('lazada', 'Image Recovery Zone');?></span>
    				            </div>
    				            <div class="sortDragArea sortDrag" style="width:99%;margin:5px;min-height:150px;">
    				                
    				            </div>
    				            <div style="clear:both;"></div>
				            </div>
				        </td>
				    </tr>
				</table>
        	</div>
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">
$(function(){
	$('ul.tabHeaderList li').click(function(){
		wish_product_update_func.changeTab(this);
	});
	$(".ftimgs .extra_checked").live('click', function(event){
		 //console.log('click');
		 event.stopPropagation();
	});
	$(".extra_checked").mousedown(function(event){
		 event.stopPropagation();
	});
});
</script>