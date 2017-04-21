<style>
<!-- 
.pageFormContent label{
	display: inline;
    float: none;
    width: auto;
}
#joom_product_add table td li{line-height:20px;}
#joom_product_add table td font.bold{font-weight:bold;}
#joom_product_add table.dataintable_inquire td td{border:none;}
#joom_product_add .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
/* #joom_product_add table{display:inline-block;} */
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
.categoryBox a.btn {
	display:block;
	float:left;
	margin-left:10px;
	border:1px #bbbbbb solid;
	background-color:#cccccc;
	line-height:22px;
	padding:5px 7px;
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
#joom_product_add table.dataintable_inquire table.variationProductTable {
	min-width:100px;
	padding:0;
	margin:10px auto;
	align:center;
	border-width:0 0 0 1px;
	border-color:#888888;
	border-style:solid;
}
#joom_product_add table.dataintable_inquire table.variationProductTable th, #joom_product_add table.dataintable_inquire table.variationProductTable td {
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
.descriptionContent label{
	width: 120px;
	line-height: 21px;
	float: left;
	padding: 0 5px;
}
-->
</style>
<script type="text/javascript">
var joom_product_add_func = {
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
			console.log(navTabId);
			$("a.BackToList").click();
			navTab.closeTab(navTabId);
		},

		//保存刊登数据
		saveInfo : function(){
			/* 在保存之前 需要把主图中的图片的checkbox置为checked*/
			$("div.ztimgs .extra_checked").attr("checked", true);
			$.ajax({
					type: 'post',
					url: $('form#joom_product_add').attr('action'),
					data:$('form#joom_product_add').serialize(),
					success:function(result){
						if(result.statusCode != '200'){
							alertMsg.error(result.message);
						}else{
							$('form#joom_product_add a.display_list').click();
							navTab.closeTab(result.navTabId);
						}
					},
					dataType:'json'
			});
		},
		//自动填写其他账号对应字段
		autoFill : function(obj) {
			var text = $(obj).val();
			$('input[group=' + $(obj).attr('group') + ']').each(function(){
				$(this).val(text);
			});
		},


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

		selectAllSku : function(self){
			var checked = !!$(self).attr('checked');
			var checkboxobj = $(self).closest('table').find('tr input[name="joom_add_selupload[]"]');
			checkboxobj.attr('checked', checked);
		},
		changeShipping : function(self, oriShipCode){
			var shipCode = $(self).val();
			if(shipCode == ''){
				return;
			}
			//确认
			alertMsg.confirm("确定要更改物流方式吗？这将会导致运费价格变动，并且已经填写的内容将不会保存！", {okCall:function(){
				var url = "<?php echo Yii::app()->request->url;?>";
				//在当前链接加上shipCode
				url += "&ship_code="+shipCode;
				navTab.reload(url);
			}, cancelCall : function(){
				//取消复原
				$(self).find('option:selected').attr('selected', false);
				$(self).find('option[value="'+oriShipCode+'"]').attr('selected', true);
			}});
		},
		
};


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
                'id' => 'joom_product_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('joom/joomproductadd/saveinfo'), 
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
    				                <li><font class="bold">SKU：</font><?php echo CHtml::link($listingProduct['sku'], '/products/product/viewskuattribute/sku/'.$listingProduct['sku'], 
			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))?></li> 
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font><?php echo $listingParam['listing_type']['text'];?></li>
				                </ul>
				                <input type="hidden" name="parent_sku" value="<?php echo $listingProduct['parentSku'];?>" />
				                <input type="hidden" name="sku" value="<?php echo $listingProduct['sku'];?>" />
				                <input type="hidden" name="publish_type" value="<?php echo $listingParam['listing_type']['id'];?>" />
				                <input type="hidden" name="product_is_multi" value="<?php echo $listingProduct['skuInfo']['product_is_multi'];?>" />
				                <input type="hidden" name="action" value="<?php echo $action;?>" />
				                <input type="hidden" name="save_type" value="<?php echo $saveType;?>" />
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
                                        <?php break;?>
                                        <div style="position:relative;" class="aliexpress_image">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" class="extra_checked" value="<?php echo $k;?>" style="z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[extra][]" <?php if($i == 0):?>checked<?php endif;?>/>
                                        </div>
                                        <?php $i++; endforeach;?>
                                        <?php endif;?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                <div class="page unitBox ftimgs">
                                    <div><strong><?php echo Yii::t('aliexpress_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                    	<?php $i=0; if(!empty($listingProduct['skuImg']['zt'])):?>
                                        <?php foreach($listingProduct['skuImg']['zt'] as $k=>$image):?>
                                        <?php break;?>
                                        <?php $i++;?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" class="extra_checked" checked value="<?php echo $k;?>" style="z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[extra][]" />
                                        </div>
                                        <?php endforeach;?>
                                        <?php endif;?>
                                        
                                        <?php $count = 0;?>
                                        <?php if(!empty($listingProduct['skuImg']['ft'])):?>
                                        <?php foreach($listingProduct['skuImg']['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" class="extra_checked" checked value="<?php echo $k;?>" style="z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[extra][]" />
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
                        
                        <!-- 物流选择 START-->				        
						<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('joom_listing', 'Logistics Info');?></td>
				            <td>
				            	<select name="ship_code" onchange="joom_product_add_func.changeShipping(this, '<?php echo $shipCode;?>')">
				            		<option value="">-请选择-</option>
				            		<?php if($logisticsList):?>
				            		<?php foreach ($logisticsList as $key=>$val):?>
				            		<option value="<?php echo $key;?>" <?php if($key == $shipCode){echo "selected";}?>><?php echo $val;?></option>
				            		<?php endforeach;?>
				            		<?php endif;?>
				            	</select>
				            </td>
				        </tr>
				        <!-- 物流选择 END -->
				        
                        <!-- sku属性显示START -->
                        <tr id="skuAttrRow">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Sale Attribute');?></td>
				            <td>
				                <div id="skuAttributes">
				               
				                <table id="skuAttributeTable" class="attributesTable">
				                	<thead>
				                		<tr>
				                			<th><input type="checkbox" id="joom_add_all_select" checked onclick="joom_product_add_func.selectAllSku(this)"/></th>
				                			<th><?php echo Yii::t('joom_listing', 'Sku');?></th>
				                			<?php if ($attributeList): ?>
				                			<?php foreach ($attributeList as $attribute):?>
				                			<th><?php echo $attribute['attribute_name'];?></th>
				                			<?php endforeach;?>
				                			<?php endif;?>
				                			<th><?php echo Yii::t('joom_listing', 'Inventory');?></th>
				                			<th><?php echo Yii::t('joom_listing', 'Price');?></th>
				                			<th><?php echo Yii::t('joom_listing', 'Market Recommand Price');?></th>
				                			<th><?php echo Yii::t('joom_listing', 'Shipping');?></th>
				                			<?php if($action == 'update'):?>
				                			<th><?php echo Yii::t('joom_listing', 'Upload status');?></th>
				                			<?php endif;?>
				                		</tr>
				                	</thead>
									<tbody>
				                		<?php if($listingSubSKU):?>
				                		<?php foreach ($listingSubSKU as $val):?>
				                		
										<tr id="attr_<?php echo $val['product_id'];?>">
											<td>
												<?php if ($val['skuInfo']['upload_status'] != 1):?>
												<input type="checkbox" name="joom_add_selupload[]" value="<?php echo $val['sku'];?>" checked/>
												<?php endif;?>
											</td>
											<td>
												<?php echo $val['sku'];?>
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
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][price]" value="<?php echo $val['skuInfo']['product_cost'];?>" class="required sale_price_info" sku="<?php echo $val['sku'];?>"/>
				                				<span><?php echo $val['skuInfo']['price_error'];?></span>
				                				<span style="color:red;" class="profit_info"><?php if(isset($val['skuInfo']['price_profit'])) echo $val['skuInfo']['price_profit']; ?></span>
				                			<?php else:?>
				                				<?php echo $val['skuInfo']['product_cost'];?>
				                			<?php endif;?>
				                			</td>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][market_price]" value="<?php if(isset($val['skuInfo']['market_price'])) echo $val['skuInfo']['market_price']; else echo $val['skuInfo']['product_cost'];?>" class="required"/>
				                			<?php else:?>
				                				<?php if(isset($val['skuInfo']['market_price'])) echo $val['skuInfo']['market_price']; else echo $val['skuInfo']['product_cost'];?>
				                			<?php endif;?>
				                			</td>
				                			<td>
				                			<?php if ($val['skuInfo']['upload_status'] != 1):?>
				                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][shipping]" value="<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>" class="required" />
				                			<?php else:?>
				                			<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>
				                			<?php endif;?>
				                			</td>
				                			<?php if($action == 'update'):?>
				                			<td>
				                				<?php echo $val['skuInfo']['upload_status_text'];?>
				                			</td>
				                			<?php endif;?>
										</tr>
										<?php endforeach;?>
										<?php else:?>
										<tr id="attr_<?php echo $listingProduct['skuInfo']['id'];?>">
											<td>
												<?php if(!$listingProduct['skuInfo']['upload_status']):?>
												<input type="checkbox" name="joom_add_selupload[]" value="<?php echo $listingProduct['sku'];?>" checked/>
												<?php else:?>
												&nbsp;
												<?php endif;?>
											</td>
											<td>
												<?php echo $listingProduct['sku'];?>
												<input type="hidden" name="skuinfo[<?php echo $listingProduct['sku'];?>][sku]" value="<?php echo $listingProduct['sku'];?>" />
											</td>
											<?php if ($attributeList): ?>
				                			<?php foreach ($attributeList as $attribute):?>
				                			<td>
				                				<?php if(!$listingProduct['skuInfo']['upload_status']):?>
				                				<input type='text' name='skuinfo[<?php echo $listingProduct['sku'];?>][<?php echo $attribute['attribute_name'];?>]' value="<?php if(isset($listingProduct['attribute'][$attribute['id']])){ echo $listingProduct['attribute'][$attribute['id']]['attribute_value_name']; } ?>">
				                				<?php else:?>
				                				<?php if(isset($listingProduct['attribute'][$attribute['id']])){ echo $listingProduct['attribute'][$attribute['id']]['attribute_value_name']; } ?>
				                				<?php endif;?>
				                			</td>
				                			<?php endforeach;?>
				                			<?php endif;?>
				                			<td>
				                				<?php if(!$listingProduct['skuInfo']['upload_status']):?>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][inventory]" value="<?php echo $listingProduct['skuInfo']['inventory'];?>" class="required"/>
				                				<?php else:?>
				                				<?php echo $listingProduct['skuInfo']['inventory'];?>
				                				<?php endif;?>
				                			</td>
				                			<td>
				                				<?php if(!$listingProduct['skuInfo']['upload_status']):?>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][price]" value="<?php echo $listingProduct['skuInfo']['product_cost'];?>" class="required sale_price_info" sku="<?php echo $listingProduct['sku'];?>"/>
				                				<span><?php echo $listingProduct['skuInfo']['price_error'];?></span>
				                				<span style="color:red;" class="profit_info"><?php if(isset($listingProduct['skuInfo']['price_profit'])) echo $listingProduct['skuInfo']['price_profit']; ?></span>
				                				<?php else:?>
				                				<?php echo $listingProduct['skuInfo']['product_cost'];?>
				                				<?php endif;?>
				                			</td>
				                			<td>
				                				<?php if(!$listingProduct['skuInfo']['upload_status']):?>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][market_price]" value="<?php echo $listingProduct['skuInfo']['market_price'];?>" class="required"/>
				                				<?php else:?>
				                				<?php echo $listingProduct['skuInfo']['market_price'];?>
				                				<?php endif;?>
				                			</td>
				                			<td>
				                				<?php if(!$listingProduct['skuInfo']['upload_status']):?>
				                				<input type="text" name="skuinfo[<?php echo $listingProduct['sku'];?>][shipping]" value="<?php echo $listingProduct['skuInfo']['shipping'];?>" class="required" />
				                				<?php else:?>
				                				<?php echo $listingProduct['skuInfo']['shipping'];?>
				                				<?php endif;?>
				                			</td>
										</tr>
										<?php endif;?>
									</tbody>
								</table>
				              
				                </div>
				                <div id="productVariations">
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
                             			    		<?php if($account['is_add'] || $isSubSku):?>
                             			    		<span><?php echo $account['title'];?></span>
                             			    		<?php else:?>
                             			    		<input type="text" class="required" name="baseinfo[<?php echo $accountID?>][subject]" value="<?php echo $account['title'];?>" onKeyDown = "joom_product_add_func.checkStrLength(this,300)" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    		<?php endif;?>
                             			    	</td>
                            	            </tr>
                            	            <tr>
                             			        <td>
                             			    		<span><?php echo Yii::t('joom_listing', 'Product Tags');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<?php if(!$account['tags']):?>
		                             			    	<?php if($account['is_add'] || $isSubSku):?>
	                             			    		-
	                             			    		<?php else:?>
		                             			    	<input class="required" type="text" name="baseinfo[<?php echo $accountID?>][tags][]" style="float:none;" value="" size="25"/>&nbsp;&nbsp;
		                             			    	<?php endif;?>
	                             			    	<?php else:?>
	                             			    	<?php foreach ($account['tags'] as $tag):?>
	                             			    	<?php if($account['is_add'] || $isSubSku):?>
	                             			    		<?php if($tag):?>
	                             			    		<span><?php echo $tag;?></span>&nbsp;&nbsp;&nbsp;&nbsp;
	                             			    		<?php endif;?>
	                             			    	<?php else:?>
														<input class="required" type="text" name="baseinfo[<?php echo $accountID?>][tags][]" style="float:none;" value="<?php echo $tag;?>" size="25"/>&nbsp;&nbsp;
	                             			    	<?php endif;?>
	                             			    	<?php endforeach;?>
	                             			    	<?php if(!($account['is_add'] || $isSubSku)):?>
	                             			    	<?php endif;?>
	                             			    	<?php endif;?>
                             			    	</td> 
                            	            </tr>
                   	           				<tr>
                   	           					<td>
                             			    		<span><?php echo Yii::t('joom_listing', 'Brand Name');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<?php if($account['is_add'] || $isSubSku):?>
                             			    		<span><?php echo $account['brand'];?></span>
                             			    		<?php else:?>
                             			    		<input  type="text" name="baseinfo[<?php echo $accountID?>][brand]" value="<?php echo $account['brand'];?>"/>
                             			    		<?php endif;?>
                             			    	</td>
                   	           				</tr>
                            	            
                            	            
					                        <!-- 产品描述START -->
					                        <tr>
					                        	<td><?php echo Yii::t('joom_listing', 'Product Description');?></td>
					                        	<td>
					                        		<textarea <?php if($account['is_add'] || $isSubSku): echo "disabled"?><?php endif;?> style="width: 90%;height:300px;" name="baseinfo[<?php echo $accountID?>][detail]" class="productDescription required"><?php echo $account['description']?></textarea>
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
                       
                        <!-- 基本信息显示END -->
				    </tbody>
				</table>
				<div class="formBar">
                    <ul>
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent"> 
                                    <a class="saveBtn" onClick="joom_product_add_func.saveInfo();" href="javascript:void(0)"><?php echo Yii::t('lazada', 'Save Into List');?></a>&nbsp;
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('joom/joomproductaddlist/index');?>" target="navTab" style="display:none;" class="display_list">
                        	<?php echo Yii::t('joom_listing','Product Add List');?>
                        </a>
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
		joom_product_add_func.changeTab(this);
	});
	$(".ftimgs .extra_checked").live('click', function(event){
		 console.log('click');
		 event.stopPropagation();
	});
	$(".extra_checked").mousedown(function(event){
		 event.stopPropagation();
	});

	$(".sale_price_info").die("change");
	$(".sale_price_info").live("change",function(){
		changePriceValue(this);
	});	

	//修改了价格,获取利润
	function changePriceValue(obj){
		var saleprice = $.trim($(obj).val());
		var sku = $(obj).attr("sku");
		var shipping = $("input[name^='skuinfo["+sku+"][shipping]']").val();
		if(saleprice=='' || saleprice==0){
			$(obj).parent().find('.profit_info').html('');
		}else{
			$(obj).parent().find('.profit_info').html('计算中...');
			$.ajax({
				type: "GET",
				url: "/joom/joomproductadd/getjoomproductprofitinfo",
				data: "sku="+sku+"&ship_price="+shipping+"&sale_price="+saleprice,
				dataType:'json',
				success: function(result){
					if(result.statusCode == 200){
						var html = "<span style='color:red;'>利润:<b>"+result.data.profit+"</b>,利润率:<b>"+result.data.profitRate+"%</b></span>";
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