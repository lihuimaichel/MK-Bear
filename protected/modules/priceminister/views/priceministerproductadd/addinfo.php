<style>
<!-- 
.pageFormContent label{
	display: inline;
    float: none;
    width: auto;
}
.sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
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
<!-- <?php 
//var_dump($template);
// foreach ($template->product->attribute as $key => $value) {
// 	// echo "<pre>";
// 	// print_r($value);
// 	// echo $value['label']; //属性标签
// 	// echo $value['key'];  //属性键
// 	// echo $value['mandatory'];//是否创建必填
// 	// echo $value['valuetype']; //类型
// 	// echo $value['multivalued'];//是否多值属性
// 	// echo $value['hasvalues']; //是否有默认属性值
// 	//echo $value['valueslist'];//属性默认值列表
// 	//echo $value['valueslist']['value'];
// 	echo $value->label; 
// 	echo '<input type="text" name="">';
// }
?> -->
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
	    <div class="bg14 pdtb2 dot">
	         <strong>SKU：test</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
	    	<div class="row productAddInfo">
	    	<?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'pm_product_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('priceminister/priceministerproductadd/saveadddata'), 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
				<table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				        <!-- 刊登参数显示START -->
				    	<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('priceminister', 'Product Add Params');?></td>
				            <td>
								<ul>
    				                <li><font class="bold">SKU：</font><?php echo CHtml::link($listingProduct['sku'], '/products/product/viewskuattribute/sku/'.$listingProduct['sku'], 
			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))?></li> 
    				                <li><font class="bold"><?php echo Yii::t('priceminister', 'Listing Type')?>：</font><?php echo $listingParam['listing_type']['text'];?></li>
				                </ul>
				                <input type="hidden" name="sku" id="main_sku" value="<?php echo $listingProduct['sku'];?>" />
				                <input type="hidden" name="listing_type" value="<?php echo $listingParam['listing_type']['id'];?>" />
				                <input type="hidden" name="action" value="<?php echo $action;?>" />
				                <input type="hidden" name="type_id" value="<?php echo $type_id;?>" />
				            </td>
				        </tr>
				        <!-- 刊登参数显示END -->
				        
				        <!-- 图片信息显示START -->
				        <tr>
				           	<td width="15%" style="font-weight:bold;"><?php echo Yii::t('priceminister', 'Image Info');?>media</td>
				           	<td>
								<div class="page unitBox ztimgs">
				                    <div><strong><?php echo Yii::t('priceminister', 'Main Images');?></strong></div>
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
                                    <div><strong><?php echo Yii::t('priceminister', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php $count = 0;?>
                                        <?php if(!empty($listingProduct['skuImg']['ft'])):?>
                                        <?php foreach($listingProduct['skuImg']['ft'] as $k=>$image):?>
                                        <div style="position:relative;">
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
						<!-- <tr>
										            <td width="15%" style="font-weight:bold;"><?php //echo Yii::t('priceminister', 'Logistics Info');?>shipping</td>
										            <td>
										            	<?php //foreach ($template->shipping->configuration->zone as $key => $value) { ?>
									<p><?php //echo $value->name; ?><?php //echo $value->type; ?></p>
							<?php //}?>
										           	</td>
										        </tr>  -->
				        <!-- 物流选择 END -->
				        
				        <!-- sku属性显示START -->
				        <tr id="skuAttrRow">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('priceminister', 'Sale Attribute');?>advert</td>
				            <td>
					            <table id="skuAttributeTable" class="attributesTable">
					            <tr>
					            	<th>sku</th>
					            	<th>
					            		<ul>
										<?php if($listingSubSKU){?>
		            	 					<?php foreach ($listingSubSKU as $sonSku){?>
		            	 					<li style="float:left;width:170px;">
		            	 						<?php echo $sonSku['sku']; ?>
		            	 					</li>
		            	 					<?php }?>
		            	 				<?php }else{?>
		            	 					<li>
		            	 						<?php echo $listingProduct['sku']; ?>
		            	 					</li>
		            	 				<?php }?>
		            	 				</ul>
					            	</th>
					            </tr>
				            	<?php foreach ($template->advert->attribute as $key => $value) { ?>
				            	<?php if(in_array($value->key, PriceministerProductAdd::$REMOVE_ADVERT_ATTR)) continue;?>
				             	<tr>
	            	 				<td><span><?php echo $value->label; ?></span></td>
	            	 				<td>
										<ul>
										<?php if($listingSubSKU){?>
		            	 					<?php foreach ($listingSubSKU as $sonSku){?>
		            	 						<li style="float:left;width:170px;">
												<?php if($value->hasvalues==1){?>
													<select name="advert[<?php echo $sonSku['sku']; ?>][<?php echo $value->key;?>]" <?php if($value->mandatory==1){?>
													class="add_require"<?php }?>>
														<option value="">请选择</option>
														<?php foreach ($value->valueslist->value as $v) { ?>
														<option value="<?php echo $v?>"><?php echo $v?></option>
														<?php }?>
													</select>
												<?php }else{ ?>
													<input type="text" name="advert[<?php echo $sonSku['sku']; ?>][<?php echo $value->key;?>]" class="<?php if($value->mandatory==1){?>add_require<?php }?> textInput"
													<?php if($value->key=='sellerReference'){?>
														value="<?php echo $sonSku['sku']; ?>"
													<?php }elseif($value->key=='qty') {?>
														value="<?php echo $sonSku['skuInfo']['inventory']; ?>"
													<?php }?>
													>
												<?php }?>
												<?php if($value->mandatory==1){?>
													<span style="color:red;float:left;">(*)</span>
												<?php }?>
												</li>
											<?php }?>
										<?php }else{ ?>
											<li>
											<?php if($value->hasvalues==1){?>
												<select name="advert[<?php echo $listingProduct['sku']; ?>][<?php echo $value->key;?>]" <?php if($value->mandatory==1){?>
												class="add_require"<?php }?>>
													<option value="">请选择</option>
													<?php foreach ($value->valueslist->value as $v) { ?>
													<option value="<?php echo $v?>"><?php echo $v?></option>
													<?php }?>
												</select>
											<?php }else{ ?>
												<input type="text" name="advert[<?php echo $listingProduct['sku']; ?>][<?php echo $value->key;?>]" class="<?php if($value->mandatory==1){?>add_require<?php }?> textInput"
												<?php if($value->key=='sellerReference'){?>
													value="<?php echo $listingProduct['sku']; ?>"
												<?php }elseif($value->key=='qty') {?>
													value="<?php echo $listingProduct['skuInfo']['inventory']; ?>"
												<?php }?>
												>
											<?php }?>
											<?php if($value->mandatory==1){?>
												<span style="color:red;float:left;">(*)</span>
											<?php }?>
											</li>
										<?php }?>
										</ul>
									</td>
                	 			</tr>
                	 			<?php }?>
					            </table>
				            </td>
				        </tr>
				        <!-- sku属性显示END -->
				        <!-- 促销相关START -->
				       <!--  <tr>
				           <td  width="15%" style="font-weight:bold;">促销信息campaigns</td>
				           <td>
				       					            <table id="skuAttributeTable" class="attributesTable">
				       					            	<?php //foreach ($template->campaigns->campaign->attribute as $key => $value) { ?>
				       					             	<tr>
				       	                	 				<td><span><?php //echo $value->label; ?></span></td>
				       	                	 				<td><?php //echo $value->key; ?><?php //echo '---'.$value->valuetype; ?><?php //echo '---'.$value->mandatory; ?><input type="text" name="campaigns[<?php //echo $value->key;?>]" class="textInput"></td>
				       	                	 			</tr>
				       	                	 			<?php //}?>
				       					            </table>
				           </td>
				       </tr> -->
				        <!-- 促销相关END -->
				       
				                              				        
				        <!-- 基本信息显示START -->
				        <tr>
				            <td rowspan="2" width="15%" style="font-weight:bold;"><?php echo Yii::t('priceminister', 'Base Info');?>product</td>
				            <td>
				            	<div class="tabs"> 
	                                <div class="tabsHeader"> 
	 		                            <div class="tabsHeaderContent"> 
                            	 			<ul> 
                            	 				<?php $k = 0;foreach($listingParam['listing_account'] as $accountID => $account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['user_name'];?>&nbsp;&nbsp;</span></a>
                            	 					<input type="hidden" name="account_id[]" value="<?php echo $accountID?>" />
                            	 				</li>
                            	 				<?php $k++;endforeach;?>
                            	 			</ul> 
                            	 		</div> 
                            	 	</div>
					            	<div class="tabsContent"> 
					            		<div class="pageFormContent" style="border:1px solid #B8D0D6">
							            	<table class="baseinfoTable" width="98%">
								               	<?php foreach ($template->product->attribute as $key => $value) { ?>
								               	<?php if(in_array($value->key, PriceministerProductAdd::$REMOVE_PRODUCT_ATTR)) continue;?>
								               	<tr>
			                    	 				<td><span><?php echo $value->label; ?></span></td>
			                    	 				<td>
			                    	 					<?php if($value->hasvalues==1){?>
														<select name="product[<?php echo $value->key;?>]" <?php if($value->mandatory==1){?>class="add_require"<?php }?>>
															<option value="">请选择</option>
															<?php foreach ($value->valueslist->value as $v) { ?>
															<option value="<?php echo $v?>"><?php echo $v?></option>
															<?php }?>
														</select>
														<?php }else{ ?>
															<input type="text" name="product[<?php echo $value->key;?>]" class="<?php if($value->mandatory==1){?>add_require<?php }?> textInput" 
															<?php if($value->key=='title' || $value->key=='titre'){?>
																value="<?php echo $listingProduct['skuInfo']['title']; ?>" style="width:500px"
															<?php }elseif ($value->key=='submitterreference') {?>
																value="<?php echo $listingProduct['sku']; ?>"
															<?php }?>
															>
														<?php }?>
														<?php if($value->mandatory==1){?>
															<span style="color:red">(*)</span>
														<?php }?>
													</td>
			                    	 			</tr>
												<?php }?>  
											</table>
										</div>	   
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
				                <a class="saveBtn" onClick="pmSaveInfo();" href="javascript:void(0)"><?php echo Yii::t('priceminister', 'Product Add List');?></a>&nbsp;
				            </div>
				        </div>
				    </li>
				    <a href="<?php echo Yii::app()->createUrl('priceminister/priceministerproductadd/index');?>" target="navTab" style="display:none;" class="display_list">
				    	<?php echo Yii::t('priceminister','Product Add List');?>
				    </a>
				</ul>
				</div>
			<?php $this->endWidget(); ?>
			</div>
		</div>
	</div>
</div>
<script>
//保存刊登数据
function pmSaveInfo(){
	var pm_add_require;
	$.each($(".add_require"),function(){
		pm_add_require = false
		if($.trim($(this).val())==''){
			pm_add_require = true;
			//alert($(this).parent().prev().find('span').html())
			//alert($(this).attr("name")+"不能为空");
			return false;
		}
	});
	if(pm_add_require==true){
		alert("有必填项未填");
		return false;
	}
	$.ajax({
		type: 'post',
		url: $('form#pm_product_add').attr('action'),
		data:$('form#pm_product_add').serialize(),
		success:function(result){
			if(result.statusCode != '200'){
				alertMsg.error(result.message);
			}else{
				$('form#pm_product_add a.display_list').click();
				navTab.closeTab(result.navTabId);
			}
		},
		dataType:'json'
	});
}
</script>