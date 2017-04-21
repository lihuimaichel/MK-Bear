<style>
<!--
/* #ebay_product_add .pageFormContent label{width:auto;} */
#ebay_product_add table td li{line-height:20px;}
#ebay_product_add table td font.bold{font-weight:bold;}
/*#ebay_product_add table.dataintable_inquire td td{border:none;}*/
#ebay_product_add .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
/* #ebay_product_add table{display:inline-block;} */

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
#ebay_product_add table.dataintable_inquire table.variationProductTable {
	min-width:100px;
	padding:0;
	margin:10px auto;
	align:center;
	border-width:0 0 0 1px;
	border-color:#888888;
	border-style:solid;
}
#ebay_product_add table.dataintable_inquire table.variationProductTable th, #ebay_product_add table.dataintable_inquire table.variationProductTable td {
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


.view_menu {
	color: #333333;
	cursor: hand;
}

.category_content {
	border: 1px solid #70B3FA;
	padding: 10px;
}

.required_option {
	font-weight: bold;
	color: red;
	font-size: 14px;
}

.item_specifics_option {
	height: 30px;
	font-weight: bold;
}

.custom_specifics_option {
	height: 30px;
	font-weight: bold;
	color: blue;
}

#variations_content {
	height: 100%;
	padding: 10px;
	border: 1px solid #70B3FA;
	padding: 5px;
	margin: 5px
}

.pageFormContent #domestic label, .pageFormContent #international label{
	width:auto;
}

.pageFormContent #domestic select,.pageFormContent #international select {
    float: none;
}
#locations{
	display:none;
}

.descriptionContent label{
	width: 120px;
	line-height: 21px;
	float: left;
	padding: 0 5px;
}

-->
</style>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
	    <div class="bg14 pdtb2 dot">
	         <strong>SKU：[<?php echo $sku;?>]</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
	       <div class="row productAddInfo" style="width:100%;float:left;">
	       <?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'ebay_product_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('ebay/ebayproductadd/saveadddata'), 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
        		<table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				        <!-- 刊登参数显示START -->
				    	<tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Product Add Params');?></td>
				            <td>
				                <ul>
    				                <li><font class="bold">SKU：</font><?php echo 
                                  		CHtml::link($sku, '/products/product/viewskuattribute/sku/'.$sku, 
                    			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
                    			?></li> 
    				                <li><font class="bold"><?php echo Yii::t('ebay', 'Listing Type')?>：</font><?php echo $listingType['text'];?></li>
    				                <li><font class="bold"><?php echo Yii::t('ebay', 'Site')?>：</font><?php echo $listingSite['text'];?></li>
				                </ul>
				                <input type="hidden" name="sku" id="main_sku" value="<?php echo $sku;?>" />
				                <input type="hidden" name="listing_type" value="<?php echo $listingType['id'];?>" />
				                <input type="hidden" name="listing_site" value="<?php echo $listingSite['id'];?>" />
								<input type='hidden' name='add_id' value='<?php echo isset($addId) ? $addId : ''; ?>' />
								<input type='hidden' name='accountids'	value='<?php echo implode(',', $accountIds);?>' />
				            </td>
				        </tr>
				        <!-- 刊登参数显示END -->
				        
				        <!-- 图片信息显示START -->
                        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Image Info');?></td>
				            <td>
				                <div class="page unitBox">
				                    <div><strong><?php echo Yii::t('aliexpress_product', 'Main Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php if (!empty($skuImg)) { ?>
                                        <?php $count = 0;?>
                                        <?php foreach($skuImg['zt'] as $k=>$image):?>
                                        <div style="position:relative;" class="ebayztimgs">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" key="<?php echo $k;?>" class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[zt][<?php echo $k;?>]"
                                            <?php if(($count < 12 && $action != 'update') || ($action == 'update' && in_array($k, $selectedImages['zt']))):?>checked<?php endif;?>
                                            />
                                        </div>
                                        <?php $count++;?>
                                        <?php endforeach;?>
                                        <?php } ?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                                <div class="page unitBox">
                                    <div><strong><?php echo Yii::t('aliexpress_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php if (!empty($skuImg)) { ?>
                                        <?php //$count = 0;?>
                                        <?php foreach($skuImg['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="ebayftimgs">
                                            <img src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" key="<?php echo $k;?>" class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[ft][<?php echo $k;?>]"
                                            <?php if($action != 'update' || ($action == 'update' && in_array($k, $selectedImages['ft']))):?>checked<?php endif;?>
                                            />
                                        </div>
                                        <?php //$count++;?>
                                        <?php endforeach;?>
                                        <?php } ?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
				            </td>
				        </tr>
                        <!-- 图片信息显示END -->

				        <!-- 类别显示START -->
				        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Category');?></td>
				            <td>
				            	<div class="categoryBox">
				            		<div class="tabHeader">
				            			<ul class="tabHeaderList">
				            			<?php if (!empty($historyCategoryList)) { ?>
				            				<li class="tab1 on"><a href="#"><?php echo Yii::t('ebay', 'History Category');?></a></li>
				            			<?php } ?>	
				            				<li class="tab2"><a href="#"><?php echo Yii::t('aliexpress_product', 'Search Category');?></a></li>
				            				<li class="tab3"><a href="#"><?php echo Yii::t('aliexpress_product', 'Choose Category');?></a></li>
				            			</ul>
				            		</div>
				            		<div class="tabBody">
				            		<?php if (!empty($historyCategoryList)) { ?>
				            			<div id="tab1" class="tabContent" style="display: block">
				            				<select style="min-width:455px" class="categoryList" name="category_list_history" size="16" onclick="setCategory(this);" onchange="setCategory(this);">
				            					<?php foreach ($historyCategoryList as $cateID => $cateName) { ?>
				            					<option value="<?php echo $cateID;?>"<?php echo array_key_exists($cateID, $defaultHistoryCategory) ? ' selected' : '';?>><?php echo $cateName;?></option>
				            					<?php } ?>
				            				</select>
				            			</div>
				            		<?php } ?>
				            			<div id="tab2" class="tabContent">
				            				<div style="overflow:hidden;margin-bottom:10px;">
					            				<input onfocus="if (this.value == _keywords) this.value=''" onblur="if (this.value == '') this.value=_keywords;" size="125" class="textInput" type="text" name="search_keywords" value="<?php echo Yii::t('aliexpress_product', 'Input Keywords');?>" />&nbsp;&nbsp;
					            				<a class="btn" href="javascript:void(0)" onclick="searchKeywords()"><?php echo Yii::t('aliexpress_product', 'Search');?></a>
				            				</div>
				            				<div id="categoryListBox">
				            					<select class="categoryList" name="category_list_search" size="16" style="min-width:455px" onchange="setCategory(this)">
				            					</select>
				            				</div>
				            			</div>
				            			<div id="tab3" class="tabContent">
				            				<select size="16" name="category_list_choose_level_1" onclick="findSubCategory(this)">
				            				<?php foreach($chooseCategoryList as $cate) { ?>
				            					<option value="<?php echo $cate['category_id'];?>"><?php echo $cate['category_name'];?></option>
				            				<?php } ?>
				            				</select>
				            			</div>
				            		</div>
				            		<div class="tabFooter">
				            			<input size="30" class="textInput search_category_id" placeholder='输入最终分类ID确定分类' type="text" name="search_category_id" />
				            				<a href="#" onclick="searchCatFromId()" class="btn"><?php echo Yii::t('aliexpress_product', 'Search');?></a><br><br><br>
				            			<input size="125" class="textInput category_name" type="text" name="category_name" value="<?php echo current($defaultHistoryCategory);?>" />
				            			<input type="hidden" value="<?php echo key($defaultHistoryCategory);?>" name="category_id" id="category_id" class="category_id"/>
				            			<a href="#" onclick="findCategoryAttributes(1)" class="btn" id="categoryConfirmBtn"><?php echo Yii::t('aliexpress_product', 'Confirm Choose Category');?></a>
				            			<a href="#" onclick="syncCategoryList(this)" class="btn" id="syncCategoryConfirmBtn" site-id="<?php echo $listingSite['id'];?>">同步当前站点分类树</a>
				            		</div>
				            	</div>
				            </td>
				        </tr>
				        <!-- 类别显示END -->
				        
				        <!-- 第二类别显示START -->
				        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Product Second Category');?></td>
				            <td>
				            	<div class="categoryBox">
				            		<div class="tabHeader">
				            			<ul class="tabHeaderList">
				            				<li class="tab4"><a href="#"><?php echo Yii::t('aliexpress_product', 'Choose Category');?></a></li>
				            			</ul>
				            		</div>
				            		<div class="tabBody">
				            		
				            			<div id="tab4" class="tabContent">
				            				<select size="16" name="category_list_choose_level_1" onclick="findSubCategory(this)">
				            				<?php foreach($chooseCategoryList as $cate) { ?>
				            					<option value="<?php echo $cate['category_id'];?>"><?php echo $cate['category_name'];?></option>
				            				<?php } ?>
				            				</select>
				            			</div>
				            		</div>
				            		<div class="tabFooter">
				            			<input size="125" class="textInput category_name" type="text" name="category_name2" value="<?php next($defaultHistoryCategory); echo current($defaultHistoryCategory);?>" />
				            			<input type="hidden" value="<?php echo key($defaultHistoryCategory);?>" name="category_id2"  class="category_id" id="category_id2" />
				            			<a href="#" onclick="clearnSelectedCategory(this)" class="btn">清空二级类目</a>
				            		</div>
				            	</div>
				            </td>
				        </tr>
				        <!-- 第二类别显示END -->
				        
				        <!-- 共有信息START -->
				        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Base Info');?></td>
				            <td>
				            	<!------------------------- 定时刊登 -------------------------------->
								<?php if($listingType['id'] == EbayProductAdd::LISTING_TYPE_AUCTION /* && !$addId */):?>
										<div class="row">
                             			    	<span>(<?php echo '拍卖定时刊登'; ?>站点当地时间)</span>
                            	        </div>
	
										<!-- 
										<div class="row">
                             			     	<?php //echo CHtml::label(Yii::t('ebay', '预刊登时间'), 'auction_start_time'); ?>
                             			    	<?php //echo CHtml::textField('auction_start_time', isset($auctionInfo['start_time'])?$auctionInfo['start_time']:'', array('id' => 'auction_start_time',  'class'=>"date", 'dateFmt'=>"yyyy-MM-dd HH:mm:ss", 'readonly'=>'true')); ?>
                             			    	<span>(北京时间)</span>
                            	        </div>
                            	         -->
                            	        <div class="row">
                             			     	<?php echo CHtml::label(Yii::t('ebay', '是否循环刊登'), 'auction_status'); ?>
                             			    	<?php echo CHtml::checkBox('auction_status', isset($auctionInfo['auction_status'])?$auctionInfo['auction_status']:'',array('id' => 'auction_status')); ?>
                            	        </div>
										<div class="row">
                             			     	<?php echo CHtml::label(Yii::t('ebay', '刊登周期'), 'auction_plan_day'); ?>
                             			    	<?php echo CHtml::textField('auction_plan_day', isset($auctionInfo['plan_day'])?$auctionInfo['plan_day']:'', array('id' => 'auction_plan_day', 'disabled'=> !empty($auctionInfo['auction_status']) ? '' : 'disabled' )); ?>天
                            	        </div>
                            	        <!-- 
										<div class="row">
                             			     	<?php //echo CHtml::label(Yii::t('ebay', '结束时间'), 'auction_end_time'); ?>
                             			    	<?php //echo CHtml::textField('auction_end_time', isset($auctionInfo['end_time'])?$auctionInfo['end_time']:'', array('id' => 'auction_end_time', 'disabled'=> !empty($auctionInfo['auction_status']) ? '' : 'disabled',  'class'=>"date", 'dateFmt'=>"yyyy-MM-dd HH:mm:ss", 'readonly'=>'true')); ?>
                            	        </div>
                            	         -->
								<?php endif;?>
								<!------------------------- 定时刊登 -------------------------------->
								<!------------------------- 拍卖规则 -------------------------------->		
								<?php if($listingType['id'] == EbayProductAdd::LISTING_TYPE_AUCTION):?>
									<div class="row">
										<?php echo CHtml::label(Yii::t('ebay', '拍卖规则'), 'auction_rule'); ?>
	                             		<select id="auction_rule" name="auction_rule">
	                             			    <?php foreach(EbayProductAdd::getAuctionType() as $key=>$rule):?>
	                             			    <option value="<?php echo $key;?>" <?php if(isset($addInfo['auction_rule']) && $addInfo['auction_rule'] == $key):?>selected<?php endif;?>><?php echo $rule;?></option>
	                             			    <?php endforeach;?>
	                             		</select>
									</div>
								<?php endif;?>
								
								<!-------------------------------------------------------------------------------->
								<!-- 刊登参数类型(对应站点设置里的配置信息) START -->
								<div class="row">
									<?php echo CHtml::label(Yii::t('ebay', '参数类型'), 'ebay_product_add_config_type'); ?>
                             		<select id="ebay_product_add_config_type" name="config_type">
                             			    <?php foreach(EbayProductAdd::getConfigType() as $key=>$rule):?>
                             			    <option value="<?php echo $key;?>" <?php if(isset($addInfo['config_type']) && $addInfo['config_type'] == $key):?>selected<?php endif;?>><?php echo $rule;?></option>
                             			    <?php endforeach;?>
                             		</select>
								</div>
								<div class="row">
									<span>产品广告详情(<font style="color:red;">若为多属性产品，EAN,ISBN,UPC将默认继承主sku</font>)</span>
								</div>
								<div class="row">
                             		<?php echo CHtml::label(Yii::t('ebay', 'Brand'), 'listing_detail[brand]'); ?>
                             		<?php echo CHtml::textField('listing_detail[brand]', empty($addInfo['brand'])? 'Unbranded' : $addInfo['brand']); ?>
                            	</div>
                            	<div class="row">
                             		<?php echo CHtml::label(Yii::t('ebay', 'MPN'), 'listing_detail[mpn]'); ?>
                             		<?php echo CHtml::textField('listing_detail[mpn]', empty($addInfo['mpn']) ? $defaultGTIN : $addInfo['mpn']); ?>
                            	</div>
								
								<div class="row">
                             		<?php echo CHtml::label(Yii::t('ebay', 'EAN'), 'listing_detail[ean]'); ?>
                             		<?php echo CHtml::textField('listing_detail[ean]', empty($addInfo['ean']) ? $defaultGTIN : $addInfo['ean']); ?>
                            	</div>
                            	
                            	<div class="row">
                             		<?php echo CHtml::label(Yii::t('ebay', 'UPC'), 'listing_detail[upc]'); ?>
                             		<?php echo CHtml::textField('listing_detail[upc]', empty($addInfo['upc']) ? $defaultGTIN : $addInfo['upc']); ?>
                            	</div>
                            	<div class="row">
                             		<?php echo CHtml::label(Yii::t('ebay', 'ISBN'), 'listing_detail[isbn]'); ?>
                             		<?php echo CHtml::textField('listing_detail[isbn]', empty($addInfo['isbn']) ? $defaultGTIN : $addInfo['isbn']); ?>
                            	</div>
								
								<!-- 刊登参数类型(对应站点设置里的配置信息) END -->
				            </td>
				        </tr>
				        <!-- 共有信息END -->
				        
				        
				        <!-- 属性信息显示START -->
                        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Attribute Info');?></td>
				            <td id="item_specifics_content">
				                
				            </td>
				        </tr>
                        <!-- 属性信息显示END -->
                       
                        
				        <!-- 基本信息显示START -->
                        <tr>
				            <td width="120" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Base Info');?></td>
				            <td>   
				                <div class="tabs"> 
	                                <div class="tabsHeader"> 
	 		                            <div class="tabsHeaderContent"> 
                            	 			<ul> 
                            	 			    <?php foreach($listingAccount as $k=>$account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['short_name'];?>&nbsp;&nbsp;</span></a>
                            	 				</li>
                            	 				<?php endforeach;?>
                            	 			</ul> 
                            	 		</div> 
                            	 	</div>
                            	 	<div>
                            	 	
                            	 	
                            	 	</div>
                            	 	
                            	 	<div class="tabsContent"> 
											
                            	 	    <?php foreach($listingAccount as $k=>$account):?>
                            	 	    <div class="all_price pageFormContent" style="border:1px solid #B8D0D6">
                            	            <div class="row">
                             			        <?php echo CHtml::label(Yii::t('ebay', 'Title'), 'title_'.$account['id']); ?>
                             			        <!--<?php echo CHtml::textField('baseInfo[title]['.$account['id'].']', empty($addInfo['title']) ? $skuInfo['title'] : $addInfo['title'], array('id' => 'title_'.$account['id'], 'size' => '60', 'style'=>'width:700px;')) ?>-->
                             			        <?php echo CHtml::textField('baseInfo[title]['.$account['id'].']', empty($addInfo['title']) ? $account['account_title'] : $addInfo['title'], array('id' => 'title_'.$account['id'], 'size' => '60', 'style'=>'width:700px;', 'class'=>'base_title', 'onchange'=>'changeTitleValue(this)')) ?>
                             			        <span class="title_info"></span>
                            	            </div>
                            	            <?php if(!empty($account['store_category'])):?>
                            	            <div class="row store_category_<?php echo $account['id'];?>">
                             			        <?php echo CHtml::label(Yii::t('ebay', 'Store Category'), 'store_category_'.$account['id']); ?>
                             			        
                            	            </div>
                            	            <div class="row">
                            	            	<span><a href="javascript:void(0);" onclick="updateStoreCateHtml(<?php echo $account['id'];?>);">更新店铺分类</a></span>
                            	            </div>
                            	            <?php endif;?>
                            	            
                            	            <div class="row sale_price">
                             			        <?php echo CHtml::label(Yii::t('ebay', 'Sale Price'), 'sale_price_'.$account['id']); ?>
												<?php echo CHtml::textField('baseInfo[sale_price]['.$account['id'].']', empty($addInfo['start_price']) ? $skuInfo['sale_price'] : $addInfo['start_price'], array('id' => 'sale_price_'.$account['id'], 'class'=>'account_sale_price price_info', 'accountid'=>$account['id'])) ?>
                             			        <span class="profit_info" style="float:left;"></span>
                             			        <?php echo CHtml::label(Yii::t('ebay', 'Custom Profit Rate'), 'custom_profit_rate_'.$account['id'],array('style'=>'margin-left:20px;width:80px;')); ?>
                            	            	<?php echo CHtml::textField('custom_profit_rate['.$account['id'].']', '', array('id' => 'custom_profit_rate_'.$account['id'], 'class'=>'custom_profit_rate', 'accountid'=>$account['id'])) ?><span style="font-size:20px">%</span>
                            	            </div>
                            	            <div class="row discount_price">
                            	            	<?php echo CHtml::label(Yii::t('ebay', 'Discount Rate'), 'discount_rate_'.$account['id']); ?>
                             			        <?php echo CHtml::textField('baseInfo[discount_rate]['.$account['id'].']', '', array('id' => 'discount_rate_'.$account['id'], 'class'=>'discount_rate', 'accountid'=>$account['id'])) ?>
                             			        <span class="rate_info"></span>
                             			        <input type="hidden" name="baseInfo[discount_price][<?php echo $account['id']?>]" class="discount_price" value="0">
                            	            </div>
                            	            <div class="row">
                             			        <?php echo CHtml::label(Yii::t('ebay', 'Description'), 'description_'.$account['id']); ?>
                             			        <span style="color: red">下面中的图片没有进行显示，但不影响最后上传结果，请不要随便动图片部分，只修改描述和清单内容即可</span>
                             			        <textarea rows="42" cols="22" name="baseInfo[detail][<?php echo $account['id'];?>]" class="ebayproductDescription"><?php echo $account['account_detail'];?></textarea>
                             			        <span><a href="<?php echo Yii::app()->createUrl("/ebay/ebayproductdesc/previewtemplate/account_id/{$account['id']}/site_id/{$listingSite['id']}/sku/{$sku}");?>" target="__blank">描述预览</a></span>
                            	            </div>
                            	            
                            	            <div class="row">
                            	            	<?php echo CHtml::label(Yii::t('ebay', 'Exclude Shipping Location'), 'exclude_location_'.$account['id']); ?>
                            	            	<input name="excludeShippingLocation_<?php echo $account['id'];?>.name" type="text" class="textInput" value="<?php echo $account['exclude_ship_name']; ?>" readonly style="width:800px;">
                            	            	<input name="excludeShippingLocation_<?php echo $account['id'];?>.code" value="<?php echo $account['exclude_ship_code']; ?>" type="hidden">
                            	            	<a class="btnLook" href="<?php echo Yii::app()->createUrl("/ebay/ebayproductadd/getexcludeshippinglocationlookup/site_id/{$listingSite['id']}/account_id/{$account['id']}/add_id/{$addId}");?>" lookupgroup="excludeShippingLocation_<?php echo $account['id'];?>" width="800" height="600">选择</a>
                            	            </div>
                            	            <br/><br/><br/><br/>
                            	            
                            	            <!-- 刊登时长 START -->
                            	            <div class="row">
												<?php echo CHtml::label(Yii::t('ebay', '刊登时长'), "listing_duration_".$account["id"]); ?>
			                             		<select id="listing_duration_<?php echo $account['id'];?>" name="baseInfo[listing_duration][<?php echo $account['id'];?>]">
			                             			    <?php foreach($listingDurations as $key=>$day):?>
			                             			    <option value="<?php echo $key;?>" <?php if(isset($addInfo['listing_duration']) && $addInfo['listing_duration'] == $key):?>selected<?php endif;?>><?php echo $day;?></option>
			                             			    <?php endforeach;?>
			                             		</select>
											</div>
                            	            <!-- 刊登时长 END -->
                            	            
                            	            <!-- 预计刊登时间START -->
                            	            <!-- 	
                            	            <div class="row">
												<?php echo CHtml::label(Yii::t('ebay', '预计刊登时间'), "plan_upload_time_".$account["id"]); ?>
			                             		<select id="plan_upload_time_<?php echo $account['id'];?>" name="baseInfo[plan_upload_time][<?php echo $account['id'];?>]">
														<?php for($i=0;$i<24;$i++):?>			                             			    
			                             			    <option value="<?php echo $i;?>" <?php if(isset($addInfo['plan_upload_time']) && $addInfo['plan_upload_time'] == $i):?>selected<?php endif;?>><?php echo $i;?>时</option>
			                             			    <?php endfor;?>
			                             		</select>
			                             		<span style="color: red">选择的时间当天已经过去，则第二天生效</span>
											</div>
											 -->
                            	            <!-- 预计刊登时间END -->
                            	            
                            	            <!-- 物流信息START -->
                            	            <div class="row">
										         <strong>本地物流信息</strong>      
										    </div>
								        	<div class="row">
								        		<input type='button' onClick='addShippingService(<?php echo $account['id']; ?>,"domestic");' value='添加'/>
								        	</div>
								        	<div class="row" id="domestic_<?php echo $account['id']; ?>" class="shippingservice">
									            	
											</div>
									        <div class="row">
									        	<strong>国际物流信息</strong>      
									        </div>
								        	<div class="row">
								        		<input type='button' onClick='addShippingService(<?php echo $account['id']; ?>, "international");' value='添加'/>
								        	</div>
								        	<div class="row" id="international_<?php echo $account['id']; ?>" class="shippingservice">
									            	
											</div>
											
                             			</div>
                            	 		<?php endforeach;?>
                             	    </div>
                             	</div>
				                <?php //添加卖价详情说明
    				                
    				            ?>
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
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('ebay', 'Save Into List');?></a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('ebay/ebayproductadd/index');?>" target="navTab" style="display:none;" class="display_list">
                        					<?php echo Yii::t('ebay', 'Save Into List');?>
                       	</a>
                       	<!-- 
                        <li>
                            <div class="buttonActive" style="margin-left:20px;">
                                <div class="buttonContent">  
                                    <a rel="" id="product_add_next_btn" target="selectTodo" onClick="$('#ebay_product_add').submit()" href="javascript:void(0)">
                                        <?php echo Yii::t('ebay', 'Upload Now')?>
                                    </a>
                                </div>
                            </div>
                        </li>
                         -->
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	<!-- 
        	<div class="row imgArea" style="float:right;clear:none;width:9%;min-width:9%;">
        	   <table class="dataintable_inquire imgArea" width="100%" height="100%" cellspacing="1" cellpadding="3" border="0">
				    <tr>
				        <td>
				            <div>
    				            <div>
    				                <span style="display:block;"><?php echo Yii::t('ebay', 'Image Recovery Zone');?></span>
    				            </div>
    				            <div class="sortDragArea sortDrag" style="width:99%;margin:5px;min-height:150px;">
    				            
    				            </div>
    				            <div style="clear:both;"></div>
				            </div>
				        </td>
				    </tr>
				</table>
        	</div>
        	 -->
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">
	// --- 店铺分类 --
	var selectedStoreCateID = <?php echo empty($addInfo['store_category_id']) ? 0 : $addInfo['store_category_id'];?>, 
		selectedStoreCateID2 = <?php echo empty($addInfo['store_category_id2']) ? 0 : $addInfo['store_category_id2'];?>, 
		accountInfoList = <?php echo json_encode($listingAccount);?>;

function renderStoreCateHtml(){
	$.each(accountInfoList, function(i, n){
		var storeCateHtml = '<select name="baseInfo[store_category]['+n.id+']" onchange="getStoreCate(this, '+n.id+')">', storeCateHtml2 = '<select class="subcategory" name="baseInfo[store_category2]['+n.id+']">';
		storeCateHtml2 += "<option value=0>选择<option>";
		$.each(n.store_category, function(j, cate){
			var cateSelected = "";
			if(selectedStoreCateID > 0 && cate.category_id == selectedStoreCateID){
				cateSelected = "selected";
			}
			storeCateHtml += '<option value="'+cate.category_id+'" '+cateSelected+'>'+cate.category_name+'</option>';
			if(cate.subcategory.length > 0){
				$.each(cate.subcategory, function(k, subcate){
					var cateSelected2 = "";
					if(selectedStoreCateID2 > 0 && subcate.category_id == selectedStoreCateID2){
						cateSelected2 = "selected";
					}
					storeCateHtml2 += '<option value="'+subcate.category_id+'" '+cateSelected2+'>'+subcate.category_name+'</option>';
				});
			}
			
		});
		storeCateHtml += '</select>';
		storeCateHtml2 += '</select>';
		$(".store_category_"+n.id).append(storeCateHtml);
		$(".store_category_"+n.id).append(storeCateHtml2);
	});
}



function updateStoreCateHtml(accountID){
	$.ajax({
		type: "GET",
		url: "<?php echo Yii::app()->createUrl('/ebay/ebayproductadd/updatestorecategory')?>",
		data: "account_id="+accountID,
		dataType:'json',
		success: function(result){
			if(result.statusCode == 200){
				var storeCateHtml = '<select name="baseInfo[store_category]['+accountID+']" onchange="getStoreCate(this, '+accountID+')">', storeCateHtml2 = '<select class="subcategory" name="baseInfo[store_category2]['+accountID+']">';
				storeCateHtml2 += "<option value=0>选择<option>";
				$.each(result.store_category, function(j, cate){
					var cateSelected = "";
					if(selectedStoreCateID > 0 && cate.category_id == selectedStoreCateID){
						cateSelected = "selected";
					}
					storeCateHtml += '<option value="'+cate.category_id+'" '+cateSelected+'>'+cate.category_name+'</option>';
					if(cate.subcategory.length > 0){
						$.each(cate.subcategory, function(k, subcate){
							var cateSelected2 = "";
							if(selectedStoreCateID2 > 0 && subcate.category_id == selectedStoreCateID2){
								cateSelected2 = "selected";
							}
							storeCateHtml2 += '<option value="'+subcate.category_id+'" '+cateSelected2+'>'+subcate.category_name+'</option>';
						});
					}
					
				});
				storeCateHtml += '</select>';
				storeCateHtml2 += '</select>';
				$('select[name="baseInfo[store_category]['+accountID+']"]').remove();
				$('select[name="baseInfo[store_category2]['+accountID+']"]').remove();
				$(".store_category_"+accountID).append(storeCateHtml);
				$(".store_category_"+accountID).append(storeCateHtml2);
			}else{
				alert(result.message);
			}
		}
	});
		

}

function getStoreCate(obj, accountId){
	var storeCategories, selectedCateId = $(obj).val(), storeCateHtml2 = '';
	$.each(accountInfoList, function(i, n){
		if(n.id == accountId){
			storeCategories = n.store_category;
			if(storeCategories[selectedCateId].subcategory){
				storeCateHtml2 = "<option value=0>选择</option>";
				$.each(storeCategories[selectedCateId].subcategory, function(k, subcate){
					storeCateHtml2 += '<option value="'+subcate.category_id+'">'+subcate.category_name+'</option>';
				});
				
			}else{
				storeCateHtml2 = "<option value=0>选择</option>";
			}
			if($(obj).parent().find("select.subcategory")){
				$(obj).parent().find("select.subcategory").html(storeCateHtml2);
			}else{
				storeCateHtml2 = '<select class="subcategory" name="baseInfo[store_category2]['+accountId+']">' + storeCateHtml2 + "</select>";
				$(obj).parent().append(storeCateHtml2);
			}
			return false;
		}
	});
}
renderStoreCateHtml();


</script>

<script type="text/javascript">
	var confirmData = null;    		
	var variation_specifics = new Array();
	var variation_skus = <?php echo json_encode($variationSkus);?>;
	var variations = "<?php echo implode(",", $attributes);?>".split(",");		
	var variation_num = 0;//当前多样性属性编号,用于添加或删除的关联性操作      
	var defaultGTIN = "<?php echo $defaultGTIN;?>";
	var variation_isopen = false; //是否开启多样性
	var variation_detail = <?php echo isset($variationDetail) ? $variationDetail : '';?>;
	var variation_gtins = <?php echo isset($variationGtins) ? $variationGtins : "''"?>;
	var categoryID = $('#category_id').val();
	if(categoryID){
		findCategoryAttributes(0);
	}
	$('input[name="get_category"]').change(function(){
		var id = $(this).attr('id');
		switch (id){
			case 'history_category':
				$(this).parent().find('a').click();
				break;
			case 'suggest_category':

				break;
			case 'choose_category':
				$.pdialog.open(
			    		'<?php echo Yii::app()->createUrl('ebay/ebaycategory/categorytree', array('site_id' => $listingSite['id'])) ?>', 
			    		'1', 
			    		'<?php echo Yii::t('ebay','Please Choose Category');?>',
			    		{width:400, height:300, mask:true, fresh:true}
			    );
				break;
		}
	});

	$('.sortDragShow div').live('mouseover', function(){
		$(this).find('div.sortDragDel').show();
	});
	
	$('.sortDragShow div').live('mouseout', function(){
		$(this).find('div.sortDragDel').hide();
	});
	$(".price_info, .account_sale_price").die("change");
	$(".price_info, .account_sale_price").live("change",function(){
		changePriceValue(this);
	});
	$(".custom_profit_rate").die("change");
	$(".custom_profit_rate").live("change",function(){
		changeProfitRateValue(this);
	});

	//修改利润率,获取价格
	function changeProfitRateValue(obj){
		var categoryid = $("#category_id").val();
		var profit_rate = $.trim($(obj).val());
		var accountid = $(obj).siblings('.price_info').attr("accountid");
		if(variation_isopen){
			var sku = $(obj).siblings('.price_info').attr('sku');
		}else{
			var sku = $("#main_sku").val();
		}
		if(profit_rate=='' || profit_rate==0){
			$(obj).siblings('.profit_info').html('');
		}else{
			$(obj).siblings('.profit_info').html('计算中...');

			$.ajax({
				type: "GET",
				url: "/ebay/ebaycategory/getebayproductsaleprice",
				data: "site_id=<?php echo $listingSite['id'];?>&category_id="+categoryid+"&sku="+sku+"&profit_rate="+profit_rate+"&listing_type=<?php echo $listingType['id'];?>&account_id="+accountid,
				dataType:'json',
				success: function(result){
					if(result.statusCode == 200){
						if(result.data.sale_price==0){
							var html = '';
						}else{
							var html = "<span style='color:red;'>利润:<b>"+result.data.profit_info.profit+"</b>,利润率:<b>"+result.data.profit_info.profitRate+"</b></span>"+"<a href='javascript:;' onClick='alertMsg.confirm(\""+result.data.profit_info.desc+"\")'><?php echo Yii::t("common", "Show Detail");?></a>";
						}						
						$(obj).siblings('.profit_info').html(html);
						$(obj).siblings('.price_info').val(result.data.sale_price);
						// discountPriceValue($(obj).parents('.all_price').find('.discount_rate'));
					}else{
						alert("获取卖价加载失败,请重试!");
						changeCurrentStatus(false);
					}
				}
			});

		}
		matchBestShippingService();
	}
	$(".base_title").each(function(){
		changeTitleValue(this);
	});
	//拍卖定时
	$("input[name='auction_status']").click(function(){
		if(!!$(this).attr("checked")==true){
			$("input[name='auction_plan_day']").attr("disabled",false);
			$("input[name='auction_end_time']").attr("disabled",false);
		}else{
			$("input[name='auction_plan_day']").attr("disabled",true);
			$("input[name='auction_end_time']").attr("disabled",true);
		}
	});
    function doAreaSort(obj, everyDivIds, everyDivPos){
        return false;
    }

    
  	//设置选中分类
    function setCategory(obj, t) {
    	clearnSelectedCategory(obj);
    	var text;
    	if (typeof(t) != 'undefined')
    		text = t;
    	else
    		text = $(obj).find('option:selected').text();
		var id = $(obj).val();
		$(obj).parents("div.categoryBox").find(".category_name").val(text);
		$(obj).parents("div.categoryBox").find(".category_id").val(id);
    	/* $('input[name=category_name]').val(text);
    	$('input[name=category_id]').val($(obj).val()); */
    }
    //清除选择的分类
    function clearnSelectedCategory(obj) {
    	clearnAttributes();
    	$(obj).parents("div.categoryBox").find(".category_name").val('');
		$(obj).parents("div.categoryBox").find(".category_id").val(0);
		
    	/* $('input[name=category_name]').val('');
    	$('input[name=category_id]').val(0); */
    	
    }
    function clearnAttributes(){

    }

    //切换TAB
    function changeTab(obj) {
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
    }

  //查找子类别
    function findSubCategory(obj) {
    	clearnSelectedCategory(obj);
    	var cateID = $(obj).val();
    	var url = '<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/categorytree/site_id/' + <?php echo $listingSite['id'];?> + '/category_id/' + cateID;
    	$.get(url, function(data){
    		$(obj).nextAll().remove();
    		if (data.statusCode == '200') {
    			var level = data.level;
    			var categoryList = data.category_list;
    			var html = '<select size="16" name="category_list_choose_level_' + level + '" onclick="findSubCategory(this)">' + "\n";
    			for (var i in categoryList)
    				html += '<option value="' + i + '">' + categoryList[i]['category_name'] + '</option>' + "\n";
    			html += '</select>' + "\n";
    			$(obj).after(html);
    			return true;
    		} else {
    			var text = $(obj).find('option:selected').text();
    			$(obj).prevAll('select').each(function(){
    				text = ($(this).find('option:selected').text()) + '->' + text;
    			});
    			setCategory(obj, text);
    			return false;
    		}
    	}, 'json');
    }
    
    //根据关键词搜索产品分类
    function searchKeywords() {
     	var keywords = $('input[name=search_keywords]').val();
    	if (keywords == '' || keywords == _keywords) {
    		alertMsg.error('<?php echo Yii::t('aliexpress_product', 'Please Input Keywords');?>');
    		return false;
    	}
    	$('select[name=category_list_search]').empty();
    	var url = '<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/getcategorysuggest/keyword/' + keywords + '/site_id/'+ <?php echo $listingSite['id'];?>;
    	$.get(url, function(data){
    		if (data.statusCode == '200') {
    			var categoryList = data.categoryList;
    			var options;
    			for (var i in categoryList) {
    				options += '<option value="' + categoryList[i].categoryid + '">' + categoryList[i].categoryname + '</option>' + "\n";
    			}
    			$('select[name=category_list_search]').html(options);
    		} else {
    			alertMsg.error(data.message);
    			return false;
    		}
    	}, 'json');
    }

    function searchCatFromId(){
    	var cateID = $('input[name=search_category_id]');
    	if($.trim(cateID.val())==''){
    		return false;
    	}
    	var url = '<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/getCategoryById/category_id/' + cateID.val() + '/site_id/'+<?php echo $listingSite['id'];?>;

    	$.get(url, function(data){
			if (data.statusCode == '200') {
				setCategory(cateID,data.data.category_name);
			}else{
				alert(data.message);
			}
    	}, 'json');

    }

  	//查找分类下的属性
    function findCategoryAttributes(type) {
    	clearnAttributes();
    	var cateID = $('input[name=category_id]').val();
    	if (cateID == '0')
    		return false;
    	var url = '<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/findcategoryinfo/category_id/' + cateID + '/site_id/'+<?php echo $listingSite['id'];?>+'/account_id/<?php echo implode(',', $accountIds);?>/sku/<?php echo $sku;?>/listing_type/<?php echo $listingType['id'];?>/add_id/<?php echo isset($addId)?$addId:'';?>';
    	$.get(url, function(data){
    		if(data){
    			if (data.statusCode == '200') {
					var html = '';
					confirmData = data.data;
					//重选分类时初始化已添加属性
					variation_specifics = new Array();
					//在加载运费模板时进行了获取
					if(type == 1){
						//console.log("====== 333333=======");
						updateConfirmContent();
					}
					//
				}else{
					alert("分类属性加载失败,请重试2!");
					changeCurrentStatus(false);
				}
			}else{
				alert("分类属性加载失败,请重试1!");
				changeCurrentStatus(false);
			}
    	}, 'json');
    }

  //确定分类后,更新分类的属性和特性
    function updateConfirmContent(){
		if(confirmData == null) return ;
    	//显示condition的内容
    	$("#item_specifics_content").html('');
    	if($("#item_specifics_option_condition").size()==0){
    		var condition_info = "<div id='item_specifics_option_condition' class='row'><label for='condition_id'>Condition</label>";
    		var ConditionEnabled = confirmData.category_features.ConditionEnabled;
    		if(ConditionEnabled=='Enabled' || ConditionEnabled=='Required'){
    			var ConditionValues = confirmData.category_features.ConditionValues;
    			condition_info += "<select name='condition_id' id='condition_id' style='width:200px;' class='required'>";
    			//condition_info +="<option value=''>请选择</option>";
    			if(!ConditionValues.Condition.length){
    				condition_info +="<option value='"+ConditionValues.Condition.ID+"'>"+ConditionValues.Condition.DisplayName+"</option>";		
    			}else{
    				$.each(ConditionValues.Condition,function(i,item){
    					var selected = '';
    					if(item.ID == confirmData.base_info.conditionid){
    						selected = ' selected ';
    					}
    					condition_info +="<option value='"+item.ID+"' "+selected+">"+item.DisplayName+"</option>";		
    				});
    			}
    		}else{
        		condition_info += "<select name='condition_id' id='condition_id' style='width:200px;'>";
    			condition_info +="<option value='-1'>ebay没有可选的值</option>";
    		}
    		condition_info +="</select></div>";
    		$("#item_specifics_content").html(condition_info);
    		//判断是否可创建variation
    		if(<?php echo $listingType['id']; ?>==<?php echo EbayProductAdd::LISTING_TYPE_VARIATION ?>){
    			if(confirmData.category_features.VariationsEnabled == 'true'){
    				//if(!confirmData.special_values){//新刊登
    					$("#item_specifics_content").append("<div class='item_specifics_option row' id='variations_content'><input type='button' value='创建多样性' onClick='createVariations()'/></div>");
    					createVariations();
    				//}
    			}else{
    				$("#item_specifics_content").append("<div class='item_specifics_option row' id='variations_content'>此分类不能创建多样性</div>");
    			}
    		}

    		var has_specific = false;
    		//显示可选属性
    		$.each(confirmData.category_specifics,function(i,item){
    			has_specific = true;
    			if (item.Name.length != undefined && $.inArray(item.Name.toLowerCase(),variations)<0){//产品属性在推荐属性里没有
    				addSpecificsItem(item);
    			}
    		});
    		
    		if(!has_specific){
    			$("#item_specifics_content").append("<div class='item_specifics_option'>此分类没有推荐属性!</div>");
    		}
    		
    		if(confirmData.special_values!=undefined){
    			$.each(confirmData.special_values,function(i,item){
    				var idnamesuffix = item.name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
    				if(item.custom=='1'){
    					addCustomSpecificsItem(item.name,item.value);
    				}else{
    					var obj = document.getElementById(idnamesuffix);
    					var option = $(obj).find("option[value='"+item.value+"']");
    					if(option.size()==1){
    						$(option).attr('selected',true);
    						changeSpecificsSelectValue(obj,"");
    					}else{
    						$(obj).val('customvalue');
    						changeSpecificsSelectValue(obj,"");
    						if(document.getElementById("customvalues_"+idnamesuffix)){
    							document.getElementById("customvalues_"+idnamesuffix).value = item.value;
    						}else if(document.getElementById("customvalues_specific_"+idnamesuffix)){
    							document.getElementById("customvalues_specific_"+idnamesuffix).value = item.value;
    						}
     						
     						//$("input[name='']")
    					}
    				}
    			});
    		}
    		//添加自定义属性
    		addCustomSpecificsItem();
    	}
    	//显示价格
    	$.each(confirmData.saleprices,function(i,item){
    		$("#sale_price_"+item.account_id).val(item.sale_price);
    		//console.log("====11111====");
    		changePriceValue($("#sale_price_"+item.account_id));

    		if(item.discount_price  && item.discount_price !=false){
    			var rate = Math.round(item.sale_price/item.discount_price*100)/100;
	    		$("#discount_rate_"+item.account_id).val(rate);
	    		discountPriceValue($("#discount_rate_"+item.account_id));
    		}
    		
    		return;
    		//直接调用
    		var html = '';
    		if(item.sale_price==0){
				var html = '';
			}else if(item.profit_info){
				var html = "<span style='color:red;'>利润:<b>"+item.profit_info.profit+"</b>,利润率:<b>"+item.profit_info.profit_rate+"</b></span>"+"<a href='javascript:;' onClick='alertMsg.confirm(\""+item.profit_info.desc+"\")'><?php echo Yii::t("common", "Show Detail");?></a>";
			}						
    		$("#sale_price_"+item.account_id).parent().find('.profit_info').html(html);
    		
    	});
    }

  	//创建多样性
    function createVariations(){
    	//var html = "<input type='button' value='关闭多样性' onClick='closeVariations()'/><br/>可添加的多样式属性：";
    	var html = "<br/>可添加的多样式属性：";
    	//推荐的属性
    	$.each(confirmData.category_specifics,function(i,item){
    		if(item.ValidationRules.VariationSpecifics=='Enabled' || item.ValidationRules.VariationSpecifics== undefined){
    			html += "<input id=\"variation_button_"+item.Name.toLowerCase().replace(/[ |\'|\(|\)]/g,"")+"\" type='button' value=\""+item.Name.toLowerCase()+"\" onClick=\"addVariationSpecific(this.value)\"/>&nbsp;&nbsp;";
    		}
    	});
    	//自定义属性
    	html += "<input type='text' id=\"custom_variation_specific\" size='20'/>";
    	html += "<input type='button' value=\"添加自定义多属性\" onClick=\"addVariationSpecific('custom_variation_specific')\"/>&nbsp;&nbsp;";


    	html += "<br/><br/>显示图片的属性:<select id='variation_picture_specific' name='variation_picture_specific'></select><input type='button' value='确认属性' onclick='confirmVariationPicture();'/>";
    	
    	
    	html += "<table id='variation_list' width='100%' border='1' cellspacing='0' cellpadding='0' bordercolordark='#FFFFFF' bordercolorlight='#999999' style='margin:5px;padding:3px;'>";
    	html += "</table>";

    	$("#variations_content").html(html);
    	//加载头部
    	addVariationHeader();
    	//加载sku
    	$.each(variation_skus,function(i,item){
    		addVariationRow(item);
    	});

    	//隐藏item的价格框
    	$(".sale_price").find("input").attr("disabled",true);
    	$(".sale_price").hide();
    	//隐藏item的折价框
    	$(".discount_price").find("input").attr("disabled",true);
    	$(".discount_price").hide();
    	
    	//加载多属性列
    	if(variation_detail){	//存在产品多属性
    		$.each(variations,function(i,item){
    			addVariationSpecific(item,1);
    		});
    	}
    	<?php if(!empty($addId)):?>//
    	updateVariationPrice(1);//如果为修改，加载卖价，不执行改价操作
    	<?php else:?>
    	updateVariationPrice();//加载卖价，执行改价操作
    	<?php endif;?>
    	variation_isopen = true;
    }

  //添加多样性头部
    function addVariationHeader(){
    	var html = "<tr id='variation_header' style='height:30px;font-size:14px;font-weight:bold;'>";
    	html +="<td width='100'>SKU";
    	html += "<br/><input type='text' id='custom_variation_sku' size='6'/>";
    	html += "<input type='button' value='添加' onClick=\"customCreateVariation(document.getElementById('custom_variation_sku').value)\"/>"
    	html +"</td>";
    	html +="<td width='200'>价格(<?php echo $currency;?>)<br/>同账号:<select id='variation_price_type'><option value='1'>同利润率</option><option value='2'>同卖价</option></select><input type='button' value='更新' onclick='updateVariationPrice();'/><div style='margin-top:3px;'>价格 <input type='text' id='custom_variation_price' size='3'/>折扣率<input type='text' id='custom_variation_rate' size='3'/><input type='button' value='同步' onclick='updatePrice();'/>自定义利润率<input type='text' id='custom_variation_profit_rate' size='3'/>%<input type='button' value='同步' onclick='updateProfitRate();'/><span id='err_msg' style='color:red'></span></div></td>";
    	html +="<td width='200'>GTIN (UPC/EAN/ISBN)</td>";// lihy add 2016-01-26
    	html += '</tr>';
    	$("#variation_list").append(html);
    }


	/**
	 *
	 *  验证自定义添加的sku
	 * 
	 *
	 */
	function customCreateVariation(sku){
		//验证改sku有效性
		sku = $.trim(sku);
    	//判断sku是否填写,至少4位
    	if(sku.length<4){
    		$("#custom_variation_sku").focus();
    		alert("请填写正确的SKU再添加!");
    		return;
    	}
    	//判断sku是否存在
    	if($("input[name='variation_skus[]'][value='"+sku+"']").size()!=0){
    		$("#custom_variation_sku").focus();
    		alert("此SKU已经在列表中,不能添加多个!");
    		return;
    	}

    	$.ajax({
    		type: "GET",
    		url: "<?php echo Yii::app()->createUrl('/ebay/ebayproductadd/verifyvariationsku')?>",
    		data: "sku="+sku,
    		dataType:'json',
    		success: function(result){
    			if(result.statusCode == 200){
    				addVariationRow(sku);
    			}else{
    				alert(result.message);
    			}
    		}
    	});
	}
	
    
  	//添加多样性行
    function addVariationRow(sku){
    	sku = $.trim(sku);
    	//判断sku是否填写,至少4位
    	if(sku.length<4){
    		$("#custom_variation_sku").focus();
    		alert("请填写正确的SKU再添加!");
    		return;
    	}
    	//判断sku是否存在
    	if($("input[name='variation_skus[]'][value='"+sku+"']").size()!=0){
    		$("#custom_variation_sku").focus();
    		alert("此SKU已经在列表中,不能添加多个!");
    		return;
    	}
    	//sku,价格,图片
    	var html = '<tr>';
    	html +="<td><div class='row' style='padding:0px'><input type='text' value='"+sku+"' size='8' maxlength='20' readonly name='variation_skus[]'/>"
    		//+"<a href='' class='thickbox' target='_blank' title='点击查看产品"+sku+"的详细信息'>详情</a>"
    		+"<br/><input type='button' value='删除行' onClick='removeVariationRow(this)'/>"
    		+"</div></td>";
    	html +="<td>";
    	var accounts = <?php echo json_encode($listingAccount);?>;
    	$.each(accounts,function(accountid, accountname){
    		html +="<div class='all_price row' style='padding:0px;padding-bottom:5px;'>";
    		html += "<label style='  float: left;width: 16px;line-height: 21px;'>" + accountname.short_name + "</label>";
    		html += "<input type='text' size='8' class='price_info' maxlength='8' accountid='"+accountname.id+"' sku='"+sku+"' name=\"variation_price["+accountname.id+"]["+sku+"]\" dataType='Currency' Require='true'  msg='必须为正整数' value=''/><span class='profit_info'></span>";
    		html += "<br />折扣率<input type='text' size='8' class='discount_rate' maxlength='8' accountid='"+accountname.id+"' sku='"+sku+"' name=\"variation_discount_rate["+accountname.id+"]["+sku+"]\" dataType='Currency' Require='true'  msg='必须为正整数' value=''/><span class='rate_info'></span><br /><input type='hidden' name='variation_discount_price["+accountname.id+"]["+sku+"]' class='discount_price' value='0'>";
    		html +="<br />自定义利润率<input type='text' size='8' class='custom_profit_rate' maxlength='8' accountid='"+accountname.id+"' sku='"+sku+"' name=\"custom_profit_rate["+accountname.id+"]["+sku+"]\" dataType='Currency' Require='true'  msg='必须为正整数' value=''/>%";
    		html +="</div>";
    	});
    	html +=	"</td>";
    	html +="<td><div class='row' style='padding:0px'>";
    	var gtin = upc = ean = isbn = defaultGTIN;
    	try{
    		if(variation_gtins[sku] && variation_gtins[sku].GTIN){
    			gtin = variation_gtins[sku].GTIN;
    		}
    	}catch(e){
    		
    	}
    	// lihy add 2016-01-26
    	html +="<input type='text' value='"+gtin+"' size='30' maxlength='30' name='variation_gtin["+sku+"]'/>";
    	html +="</div></td>";
    	//自定义值
    	//每行内容添加属性
    	for(var i=0;i<variation_specifics.length;i++){
    		var item_info = ''; 
    		$.each(confirmData.category_specifics,function(j,item){
    			if(item.Name.toLowerCase() != variation_specifics[i].toLowerCase()){
    				return;
    			}else{
    				item_info = item;
    			}
    		});
    		name = variation_specifics[i];
    		var idnamesuffix = name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
    		var variation_num = $(document.getElementById('variation_num_'+idnamesuffix)).val();
    		html += "<td class='variation_num"+variation_num+"'><div class='row' style='padding:0px'>";
    		if(item_info == ''){
    			html += "<input type='text' name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+name+"\" size='15' dataType='Require' msg='必填' />";
    		}else{
    			html += "<select type='text' class=\""+idnamesuffix+"\" itemname=\""+name+"\" name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+idnamesuffix+"\" onchange='changeSpecificsSelectValue(this,\""+sku+"\")' dataType='Require' msg='必选'>";
    			html += "<option value=''>请选择</option>";
    			if(item_info.ValueRecommendation!=undefined){
					if(item_info.ValueRecommendation.Value!=undefined){//一个值
	    				html += "<option value='"+item_info.ValueRecommendation.Value+"'>"+item_info.ValueRecommendation.Value+"</option>";
	    			}else{
	    				$.each(item_info.ValueRecommendation,function(i,ele){
	    					html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";
	    				});
	    			}
    			}
    			if(item_info.ValidationRules.SelectionMode=='FreeText'){
    				html += "<option value='customvalue'>自定义</option>";
    			}
    			html += "</select>";
    		}
    		html += "</div></td>";
    	}//end for

    	html += "</tr>";
    	$("#variation_list").append(html);
    }


  	//添加一个推荐属性
    function addSpecificsItem(item){
		//@todo test
        //var idnamesuffix = item.Name.toLowerCase();
        var idnamesuffix = item.Name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
    	var html = "<div id=\"item_specifics_option_"+idnamesuffix+"\" class='item_specifics_option item_specifics_option_c row'>";
    	var minValues = item.ValidationRules.MinValues!=undefined ? item.ValidationRules.MinValues : 0;//至少选择的值(0为可选,0以上为必须数量)
    	//属性名称
    	//html += "<span class="+(minValues>0?"required_option":"")+">"+item.Name+"：</span>";
    	html += "<label for='specifics_value_"+idnamesuffix+"' "+(minValues>0?"style='color:red;' ":"")+">"+item.Name+"</label>";
    	//属性值
    	html += "<span id=\"specifics_value_"+idnamesuffix+"\">";
    	if(item.ValidationRules.SelectionMode=='Prefilled'){
    		html += "无需填写,ebay有默认值!";
    	}else{
    		if(item.ValueRecommendation==undefined){//不可选择,使用text框
    			html += "<input type='text' class=\""+idnamesuffix+"\" name=\"specifics["+item.Name+"]\" itemname=\""+item.Name+"\" id=\""+idnamesuffix+"\" size='30' "+(minValues>0?"dataType='Require' msg='必填' ":"")+"/>";	
    		}else{
    			var parentName = '';
    			if(item.ValidationRules.Relationship!=undefined){
    				parentName += item.ValidationRules.Relationship.ParentName;
    			}
    			
    			html += "<select type='text' class=\""+idnamesuffix+"\" name=\"specifics["+item.Name+"]\" itemname=\""+item.Name+"\" id=\""+idnamesuffix+"\" onchange='changeSpecificsSelectValue(this,\"\")' "+(minValues>0?"dataType='Require' msg='必选' ":"")+">";
    			if(item.ValidationRules.Relationship!=undefined){//有父属性
    				html += "<option value=''>请先选择"+parentName+"再选择"+item.Name+"</option>";
    			}else{
    				var flagCustom = false;
    				html += "<option value=''>请选择</option>";
    				if(item.ValueRecommendation.Value!=undefined){//一个值
    					html += "<option value='"+item.ValueRecommendation.Value+"' selected>"+item.ValueRecommendation.Value+"</option>";
    				}else{
    					$.each(item.ValueRecommendation,function(i,ele){
    						var selected = '';
    						if( item.Name == 'Brand' && (ele.Value == 'Unbranded/Generic' || ele.Value == 'Unbranded') ){
    							flagCustom = true;
    							selected = ' selected ';
    						}
    						if( item.Name == 'MPN' && (ele.Value == 'Does Not Apply') ){/*这里根据站点不一样而要做区别*/
    							selected = ' selected ';
    						}
    						html += "<option value='"+ele.Value+"' "+selected+">"+ele.Value+"</option>";
    					});
    				}
    				if(item.ValidationRules.SelectionMode=='FreeText'){
    					if( item.Name == 'Brand' && !flagCustom ){
    						html += "<option value='customvalue' selected>自定义</option>";
    						html += "<input type='text' id=\"customvalues_"+idnamesuffix+"\" name=\"specific_customvalues["+item.Name+"]\" size='12' value=\"Unbranded\" />";
    					}else{
    						html += "<option value='customvalue'>自定义</option>";
    					}
    				}
    			}
    			html += "</select>";
    		}
    	}
    	html += "</span>";
    	var message = '';
    	if(item.HelpText!=undefined){
    		message += item.HelpText;
    	}
    	if(item.HelpURL!=undefined){
    		message += "<a href='"+item.HelpURL+"' target='_blank'>帮助链接.</a>";
    	}
    	//提示
    	html += "<span id=\"specifics_message_"+item.Name+"\">"+message+"</span>";
    	html += "</div>";
    	$("#item_specifics_content").append(html);
    }

  //添加一个自定义属性
    function addCustomSpecificsItem(name,value){
    	if(name==undefined){
    		name='';
    	}
    	if(value==undefined){
    		value='';
    	}
    	var customQty = $(".custom_specifics_option").size();
    	var html = "<div class='custom_specifics_option row'>";
    	//属性名称
    	html += "<span><input type='text' name='custom_specific_names[]' size='20' value='"+name+"'/>：</span>";
    	//属性值
    	html += "<span><input type='text' name='custom_specific_values[]' size='20' value='"+value+"' onchange='changeAttrVal(this)'/></span>";
    	if(customQty>0){
    		html += "<input type='button' value='删除' onClick='removeCustomSpecificsItem(this)'/>";	
    	}else{
    		html += "<input type='button' value='添加自定义属性' onClick='addCustomSpecificsItem()'/>";
    	}
		html += "<span class='attrv_info'></span>";
    	html += "</div>";
    	
    	$("#item_specifics_content").append(html);
    }

  	//删除一个自定义属性
    function removeCustomSpecificsItem(obj){
    	$(obj).parent().remove();
    }

  	//改变属性选择的值
    function changeSpecificsSelectValue(obj,sku){
    	//判断是否为自定义
    	var specificName = $(obj).attr('itemname');//@todo 请注意这里可是留了一个很大的坑，我也被坑住了，但没来及修改   2016-07-21 已经修改了，但是可能有遗留，所以还是提醒显示
    	var pidnamesuffix = $(obj).attr('class');
    	if($(obj).val()=='customvalue'){
    		//if(document.getElementById("customvalues_"+pidnamesuffix)==undefined){
    		if($(obj).next("input[type='text']").size()==0){
    			//添加text框
    			var namevalue = sku=='' ? "specific_customvalues["+specificName+"]" : "specific_customvalues["+sku+"]["+specificName+"]"; 
    			var html = "<input type='text' name=\""+namevalue+"\" id=\"customvalues_"+pidnamesuffix+"\" size='12' />";
    			$(obj).parent().append(html);
    		}
    	}else{
    		//去掉text框
    		$(obj).parent().find("input:text").remove();
    	}
    	var specificValue = $(obj).val();
    	//判断是否有子属性
    	$.each(confirmData.category_specifics,function(i,item){
    		var idnamesuffix = item.Name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
    		//有父属性
    		if(item.ValidationRules.Relationship!=undefined){
    			if(item.ValidationRules.Relationship.ParentName==specificName){//找到子属性
//     				alert(item.ValidationRules.Relationship.ParentName);
//     				alert(specificName);
    				var html = "<select class=\"specific_"+idnamesuffix+"\" type='text' itemname=\""+item.Name+"\" name=\"specifics["+item.Name+"]\" id=\""+idnamesuffix+"\" onchange='changeSpecificsSelectValue(this,\"\")'>";
    				html += "<option value=''>请选择</option>";
    				$.each(item.ValueRecommendation,function(i,ele){
    					var is_match = false;
    					if(ele.ValidationRules==undefined || ele.ValidationRules.Relationship==undefined){
    						is_match = true;
    					}else{
    						//一个
    						if(ele.ValidationRules.Relationship.ParentName!=undefined){
    							//转成list
    							ele.ValidationRules.Relationship = {'':ele.ValidationRules.Relationship};
    						}
    						$.each(ele.ValidationRules.Relationship,function(i,e){
    							if(is_match){
    								return;
    							} 
    							if(e.ParentName==specificName && e.ParentValue==specificValue){
    								is_match = true;
    							}	
    						});
    					}
    					if(is_match){
    						html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";
    					}
    				});
    				if(item.ValidationRules.SelectionMode=='FreeText'){
    					html += "<option value='customvalue'>自定义</option>";
    				}
    				html += "</select>";
    				
    				var element = document.getElementById(idnamesuffix);
    				$(element).parent().html(html);
//    				$('select[class="specific_'+idnamesuffix+'"]').parent().html(html);
    			}
    		}
    	});
    }

  //添加多样性属性
  //参数说明,obj为属性名，type为只有自定义属性才识别的，0为手动添加的自定义属性，1为自动从产品读取的属性
  function addVariationSpecific(obj,type){
  	//alert(obj);
  	obj = obj.toLowerCase();//转化为小写
  	if(obj == 'custom_variation_specific'){
  		var name = $("#custom_variation_specific").val();
  	}else{
  		var name = obj;
  	}
  	if($.trim(name)==''){
  		return;
  	}
  	var idnamesuffix = name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
  	if(variation_specifics.length==5){
  		alert("多属性最多5个,不能再添加!");
  		return;
  	}

  	//判断是否已经添加
  	for(var i=0;i<variation_specifics.length;i++){
  		if(variation_specifics[i].toLowerCase()==name.toLowerCase()){
  			alert("已经在多属性中,不能再添加!");
  			return;
  		}
  	}
  	
  	//判断添加是否正确
  	var find_specific = false;
  	var item_info = '';
  	$.each(confirmData.category_specifics,function(i,item){
  		if(obj == 'custom_variation_specific'){
  			if(name.toLowerCase()==item.Name.toLowerCase()){
  				alert("不能添加与推荐属性相同的名称!");
  				find_specific = true;
  			}
  			return;
  		}
  		//找到属性
  		if(name.toLowerCase()==item.Name.toLowerCase()){
  			find_specific = true;
  			item_info = item;
  		}
  	});
  	//console.log(variation_detail);
  	if(obj == 'custom_variation_specific' && !find_specific || obj != 'custom_variation_specific' && find_specific || (type==1 && !find_specific)){
  		variation_num++;
  		//添加头部
  		var html = "<td class='variation_num"+variation_num+"'><input type='hidden' value='"+variation_num+"' id=\"variation_num_"+idnamesuffix+"\"/><span class='variation_name'>"+name+"</span><input type='button' class='variation_specific_remove_button' value='删除' onClick='removeVariationSpecific(this)'/></td>";
  		$("#variation_header").append(html);
  		//每行内容添加属性
  		$.each($("#variation_list tr"),function(i,tr){
  			if(i==0){
  				return;
  			}
  			var sku = $(tr).find("input[name='variation_skus[]']").val();
  			var html = "<td class='variation_num"+variation_num+"'>";
  			//console.log(variation_detail[sku][name]);
  			if(obj == 'custom_variation_specific' || (type==1 && !find_specific)){
  				if(type==1 && !find_specific){
  					var text_val = variation_detail[sku][name];
  				}else{
  					var text_val = '';
  				}
  				html += "<input type='text' name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+idnamesuffix+"\" size='15' dataType='Require' msg='必填' value='"+text_val+"' />";
  			}else{
  				if(item_info.ValueRecommendation==undefined){
  					html += '<input type="text" value="" msg="必填" datatype="Require" size="15" id="variation_'+idnamesuffix+'" name="variation_values['+sku+']['+name+']">';
  				}else{
  					html += "<select type='text' class=\""+idnamesuffix+"\" itemname=\""+name+"\" name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+idnamesuffix+"\" onchange='changeSpecificsSelectValue(this,\""+sku+"\")' dataType='Require' msg='必选'>";
  					html += "<option value=''>请选择</option>";
  					if(item_info.ValueRecommendation.Value!=undefined){//一个值
  						html += "<option value='"+item_info.ValueRecommendation.Value+"'>"+item_info.ValueRecommendation.Value+"</option>";
  					}else{
  						if(type!=1){//手动添加属性添加不上问题
							$.each(item_info.ValueRecommendation,function(i,ele){
		    					html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";
		    				});
  						}else{
	  						var temp_value_length = -1;//记录ebay推荐属性选择值与产品预填值的差，如预填值为bl，推荐值有blue和black都匹配，选择差值短的blue
	  						var selected_flag = false;//记录select中是否有预填值
	  						var str_attr;
	  						var strarr = variation_detail[sku];
	  						$.each(strarr, function(i, n){
								if(i.toLowerCase() == name){
									str_attr = n;
								}
	  	  					});
	  						if(type==1 && str_attr){
								//var str_attr = new RegExp("^"+str_attr.toLowerCase());
							}
							
							
	  						$.each(item_info.ValueRecommendation,function(index,ele){
	  							//if(ele.Value==variation_detail[sku][name] && type==1){
	  							//alert(str_attr.test(ele.Value.toLowerCase()));
	  							if(str_attr && str_attr == ele.Value.toLowerCase() && type==1){
	  								if(temp_value_length < 0 || (temp_value_length > 0 && ele.Value.length < temp_value_length)){
	  									temp_value_length = ele.Value.length;	
	  									html += "<option value='"+ele.Value+"' selected='selected'>"+ele.Value+"</option>";
	  									selected_flag = true;
	  								}else{
	  									html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";	
	  								}
	  							}else{
	  								html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";	
	  							}
	  						});
	  					}	
  					}
  					if(item_info.ValidationRules.SelectionMode=='FreeText'){
  						if(!selected_flag && type==1){
  							html += "<option value='customvalue' selected='selected'>自定义</option>";
  						}else{
  							html += "<option value='customvalue'>自定义</option>";
  						}
  					}
  					html += "</select>";
  					if(!selected_flag && type==1){
  						html += '<input value="'+variation_detail[sku][name]+'" type="text" size="12" id="customvalues_'+idnamesuffix+'" name="specific_customvalues['+sku+']['+name+']">';
  					}
  				}
  			}
  			html += '</td>';
  			$(tr).append(html);
  		});
  		//禁用按钮
  		if(obj != 'custom_variation_specific'){
  			//$(obj).attr("disabled",true);
  			$("#variation_button_"+obj.replace(/[ |\'|\(|\)]/g,"")).attr("disabled",true);
  		}
  		
  		//禁用item属性
  		
  		var specific_div = document.getElementById("item_specifics_option_"+idnamesuffix);
  		$(specific_div).find("select").attr("disabled",true);
  		$(specific_div).find("input").attr("disabled",true);
  		$(specific_div).hide();

  		//添加选择图片属性
  		if("<?php echo $variationPic;?>"==name){
  			$("#variation_picture_specific").append("<option value=\""+name+"\" selected='selected'>"+name+"</option>");
  		}else{
  			$("#variation_picture_specific").append("<option value=\""+name+"\">"+name+"</option>");
  		}
  		
  		//添加到属性列表
  		variation_specifics.push(name);
  	}
  }

//添加多样性属性2
//参数说明,obj为属性名，type为只有自定义属性才识别的，0为手动添加的自定义属性，1为自动从产品读取的属性
function addVariationSpecific2(obj,type){
	//alert(obj);
	obj = obj.toLowerCase();//转化为小写
	if(obj == 'custom_variation_specific'){
		var name = $("#custom_variation_specific").val();
	}else{
		var name = obj;
	}
	if($.trim(name)==''){
		return;
	}
	var idnamesuffix = name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
	if(variation_specifics.length==5){
		alert("多属性最多5个,不能再添加!");
		return;
	}

	//判断是否已经添加
	for(var i=0;i<variation_specifics.length;i++){
		if(variation_specifics[i].toLowerCase()==name.toLowerCase()){
			alert("已经在多属性中,不能再添加!");
			return;
		}
	}
	
	//判断添加是否正确
	var find_specific = false;
	var item_info = '';
	$.each(confirmData.category_specifics,function(i,item){
		if(obj == 'custom_variation_specific'){
			if(name.toLowerCase()==item.Name.toLowerCase()){
				alert("不能添加与推荐属性相同的名称!");
				find_specific = true;
			}
			return;
		}
		//找到属性
		if(name.toLowerCase()==item.Name.toLowerCase()){
			find_specific = true;
			item_info = item;
		}
	});
	
	
	if(obj == 'custom_variation_specific' && !find_specific || obj != 'custom_variation_specific' && find_specific || (type==1 && !find_specific)){
		variation_num++;
		//添加头部
		var html = "<td class='variation_num"+variation_num+"'><input type='hidden' value='"+variation_num+"' id=\"variation_num_"+idnamesuffix+"\"/><span class='variation_name'>"+name+"</span><input type='button' class='variation_specific_remove_button' value='删除' onClick='removeVariationSpecific(this)'/></td>";
		$("#variation_header").append(html);
		//每行内容添加属性
		$.each($("#variation_list tr"),function(i,tr){
			if(i==0){
				return;
			}
			var sku = $(tr).find("input[name='variation_skus[]']").val();
			var html = "<td class='variation_num"+variation_num+"'>";
			if(obj == 'custom_variation_specific' || (type==1 && !find_specific)){
				if(type==1 && !find_specific){
					var text_val = variation_detail[sku][name];
				}else{
					var text_val = '';
				}
				html += "<input type='text' name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+idnamesuffix+"\" size='15' dataType='Require' msg='必填' value='"+text_val+"' />";
			}else{
				if(item_info.ValueRecommendation==undefined){
					html += '<input type="text" value="" msg="必填" datatype="Require" size="15" id="variation_'+idnamesuffix+'" name="variation_values['+sku+']['+name+']">';
				}else{
					html += "<select type='text' class=\""+idnamesuffix+"\" itemname=\""+name+"\" name=\"variation_values["+sku+"]["+name+"]\" id=\"variation_"+idnamesuffix+"\" onchange='changeSpecificsSelectValue(this,\""+sku+"\")' dataType='Require' msg='必选'>";
					html += "<option value=''>请选择</option>";
					if(item_info.ValueRecommendation.Value!=undefined){//一个值
						html += "<option value='"+item_info.ValueRecommendation.Value+"'>"+item_info.ValueRecommendation.Value+"</option>";
					}else{
						var temp_value_length = -1;//记录ebay推荐属性选择值与产品预填值的差，如预填值为bl，推荐值有blue和black都匹配，选择差值短的blue
						var selected_flag = false;//记录select中是否有预填值
						$.each(item_info.ValueRecommendation,function(index,ele){
							//if(ele.Value==variation_detail[sku][name] && type==1){
							if(type==1){
								var str_attr = new RegExp("^"+variation_detail[sku][name].toLowerCase());
							}
							//alert(str_attr.test(ele.Value.toLowerCase()));
							if(str_attr && str_attr.test(ele.Value.toLowerCase()) && type==1){
								if(temp_value_length < 0 || (temp_value_length > 0 && ele.Value.length < temp_value_length)){
									temp_value_length = ele.Value.length;	
									html += "<option value='"+ele.Value+"' selected='selected'>"+ele.Value+"</option>";
									selected_flag = true;
								}else{
									html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";	
								}
							}else{
								html += "<option value='"+ele.Value+"'>"+ele.Value+"</option>";	
							}
						});
					}
					if(item_info.ValidationRules.SelectionMode=='FreeText'){
						if(!selected_flag && type==1){
							html += "<option value='customvalue' selected='selected'>自定义</option>";
						}else{
							html += "<option value='customvalue'>自定义</option>";
						}
					}
					html += "</select>";
					if(!selected_flag && type==1){
						html += '<input value="'+variation_detail[sku][name]+'" type="text" size="12" id="customvalues_'+idnamesuffix+'" name="specific_customvalues['+sku+']['+name+']">';
					}
				}
			}
			html += '</td>';
			$(tr).append(html);
		});
		//禁用按钮
		if(obj != 'custom_variation_specific'){
			//$(obj).attr("disabled",true);
			$("#variation_button_"+obj.replace(/[ |\'|\(|\)]/g,"")).attr("disabled",true);
		}
		
		//禁用item属性
		
		var specific_div = document.getElementById("item_specifics_option_"+idnamesuffix);
		$(specific_div).find("select").attr("disabled",true);
		$(specific_div).find("input").attr("disabled",true);
		$(specific_div).hide();

		//添加选择图片属性
		if("<?php echo $variationPic;?>"==name){
			$("#variation_picture_specific").append("<option value=\""+name+"\" selected='selected'>"+name+"</option>");
		}else{
			$("#variation_picture_specific").append("<option value=\""+name+"\">"+name+"</option>");
		}
		
		//添加到属性列表
		variation_specifics.push(name);
	}
}

//删除多样性属性
function removeVariationSpecific(obj){
	var class_name = $(obj).parent().attr('class');
	var name = $(obj).parent().find(".variation_name").text();
	//删除头部
	//删除内容
	$("."+class_name).remove();
	//开启按钮
	$(document.getElementById("variation_button_"+name.replace(/[ |\'|\(|\)]/g,""))).attr("disabled",false);
	//开启item属性
	var idnamesuffix = name.toLowerCase().replace(/[ |\'|\(|\)]/g, "");
	var specific_div = document.getElementById("item_specifics_option_"+idnamesuffix);
	$(specific_div).find("select").attr("disabled",false);
	$(specific_div).find("input").attr("disabled",true);
	$(specific_div).show();
	//删除属性列表
	for(var i=0;i<variation_specifics.length;i++){
		if(variation_specifics[i]==name){
			variation_specifics.splice(i,1);
			break;
		}
	}
	//删除选择图片属性
	$.each($("#variation_picture_specific option"),function(j,item){
		if(item.value==name){
			$(item).remove();
		}
	});
}


//删除多样性行
function removeVariationRow(obj){
	$(obj).parent().parent().parent().remove();
}

//确认多样性显示的图片
function confirmVariationPicture(){
	var variation_values = new Array();
	var specific = $("#variation_picture_specific option:selected").val();
	$.each($("input[name^=variation_pictures]"),function(i,item){
		$.each($(item).parent().parent().find("[name^=variation_values]"),function(j,ele){
			if($(ele).attr('id')=="variation_"+specific){
				var value = $(ele).val();
				if(value=='customvalue'){
					value = $(ele).parent().find("input:text").val();
				}
				value = $.trim(value+'');
				if($.inArray(value,variation_values)!=-1){
					$(item).attr('disabled',true);
				}else{
					$(item).attr('disabled',false);
					if(value!=''){
						variation_values[variation_values.length] = value;
					}
				}
			}
		});
	});
}

//同步自定义利润率到子sku
function updateProfitRate(){
	var variationProfitRate = $.trim($("#custom_variation_profit_rate").val());
	var err_msg = $("#err_msg");
	var reg = new RegExp("^[0-9]+(\.[0-9]+)?$");
	if(variationProfitRate=='' ){
		$("#err_msg").html("请输入自定义利润率");
		return;
	}
	$("#err_msg").html('');
	$.each($("#variation_list .custom_profit_rate"),function(i,item){
		$(this).val(variationProfitRate);
		changeProfitRateValue($(this));
		
	});
}
//同步价格到子sku
function updatePrice(){
	var variationPrice = $.trim($("#custom_variation_price").val());
	var variationRate = $.trim($("#custom_variation_rate").val());
	var err_msg = $("#err_msg");
	var reg = new RegExp("^[0-9]+(\.[0-9]+)?$");
	if(variationPrice=='' && variationRate==''){
		$("#err_msg").html("请输入价格或折扣率");
		return;
	}
	if(variationPrice!='' && !reg.test(variationPrice)){
		$("#err_msg").html("价格应为整数或者小数");
		return;
	}
	if(variationRate!='' && !reg.test(variationRate)){
		$("#err_msg").html("折扣率应为整数或者小数");
		return;
	}
	$("#err_msg").html('');
	if(variationPrice=='' && variationRate!=''){//只输入了折扣率
		$.each($("#variation_list .price_info"),function(i,item){
			$(".discount_rate").eq(i).val(variationRate);
			changePriceValue($(this));
		});
	}else{
		$.each($("#variation_list .price_info"),function(i,item){
			$(this).val(variationPrice);
			changePriceValue($(this));
			$(".discount_rate").eq(i).val(variationRate);
		});
	}
}
//更新variation价格
function updateVariationPrice(nochange){//参数nochange为是否不执行改价操作
	//sku
	var skus = new Array();
	$.each($("input[name='variation_skus[]']"),function(i,item){
		skus.push($(item).val());
	});
	skus = skus.join(','); 
	var pricetype = $('#variation_price_type').val();
	var accountids = "<?php echo implode(',', $accountIds);?>";
	var categoryid = $("#category_id").val();
	$.ajax({
		type: "GET",
		url: "<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/getebayvariationprice",
		data: "site_id=<?php echo $listingSite['id'];?>&category_id="+categoryid+"&skus="+skus+"&account_ids="+accountids+"&price_type="+pricetype+"&add_id="+<?php echo !empty($addId) ? $addId : 0 ?>,
		dataType:'json',
		success: function(result){
			if(result.statusCode == 200){
				//显示价格
				$.each(result.data.saleprices,function(i,item){
					var obj = $("input[name='variation_price["+item.account_id+"]["+item.sku+"]']"); 
					obj.val(item.sale_price);
					changePriceValue(obj);
					if(item.variation_discount_price && item.variation_discount_price !=false){
						var obj1 = $("input[name='variation_discount_rate["+item.account_id+"]["+item.sku+"]']");
						var rate = Math.round(item.sale_price/item.variation_discount_price*100)/100;
						obj1.val(rate);
						discountPriceValue(obj1);
					}
					
					if(!nochange){
						//测试
						//changePriceValue(obj);
						//return;
						
						/* var html = '';
						if(item.sale_price==0){
							var html = '';
						}else if(item.profit_info){
							var html = "<span style='color:red;'>利润:<b>"+item.profit_info.profit+"</b>,利润率:<b>"+item.profit_info.profit_rate+"</b></span>";
						}						
						$(obj).parent().find('.profit_info').html(html); */
					}
				});
			}else{
				alert("多样性卖价加载失败,请重试!");
			}
		}
	});
}
$(".discount_rate").die("change");
$(".discount_rate").live("change",function(){
	discountPriceValue(this);
});
//初始价，折扣率
function discountPriceValue(obj){
	var discountrate = $.trim($(obj).val());
	var saleprice = $.trim($(obj).parents('.all_price').find('.price_info').val());
	var categoryid = $("#category_id").val();
	var accountid = $(obj).attr("accountid");
	if(variation_isopen){
		var sku = $(obj).attr('sku');
	}else{
		var sku = $("#main_sku").val();
	}
	if(discountrate=='' || discountrate==0){
		$(obj).parent().find('.rate_info').html('');
		$(obj).parent().find('.discount_price').val('0');
	}else{
		//初始价
		var discountprice = Math.round(saleprice / discountrate * 100)/100;
		var html = "<span style='color:red;'>初始价:<b>"+discountprice+"</b></span>";
		$(obj).parent().find('.rate_info').html(html);
		$(obj).parent().find('.discount_price').val(discountprice);
	}
}

//修改了价格,获取利润
function changePriceValue(obj){
	var categoryid = $("#category_id").val();
	var saleprice = $.trim($(obj).val());
	var accountid = $(obj).attr("accountid");
	if(variation_isopen){
		var sku = $(obj).attr('sku');
	}else{
		var sku = $("#main_sku").val();
	}
	if(saleprice=='' || saleprice==0){
		$(obj).parent().find('.profit_info').html('');
	}else{
		$(obj).parent().find('.profit_info').html('计算中...');
		//获取最小的运费
		var shipcost = getMinShipCost(accountid);
		$.ajax({
			type: "GET",
			url: "<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/getebayproductprofitinfo",
			data: "site_id=<?php echo $listingSite['id'];?>&category_id="+categoryid+"&sku="+sku+"&ship_cost="+shipcost+"&sale_price="+saleprice+"&listing_type=<?php echo $listingType['id'];?>&account_id="+accountid,
			dataType:'json',
			success: function(result){
				if(result.statusCode == 200){
					if(result.data.sale_price==0){
						var html = '';
					}else{
						var html = "<span style='color:red;'>利润:<b>"+result.data.profit_info.profit+"</b>,利润率:<b>"+result.data.profit_info.profit_rate+"</b></span>"+"<a href='javascript:;' onClick='alertMsg.confirm(\""+result.data.profit_info.desc+"\")'><?php echo Yii::t("common", "Show Detail");?></a>";
					}						
					$(obj).parent().find('.profit_info').html(html);
					$(obj).val(result.data.sale_price);
					discountPriceValue($(obj).parents('.all_price').find('.discount_rate'));
				}else{
					alert("利润情况加载失败,请重试!");
					changeCurrentStatus(false);
				}
			}
		});

	}
	matchBestShippingService();
}

function matchBestShippingService(){
	var siteId = <?php echo $listingSite['id'];?>, listingType = <?php echo $listingType['id'];?>, accountIds = <?php echo json_encode($accountIds);?>;
	if(siteId > 0){
		return;
	}
	//ePacketChina 、EconomyShippingFromOutsideUS
	//排除海外仓
	//var filterAccountID = ['13', '37', '54', '55', '57', '59', '60', '62','34','39','69','73','74','77','75','76','78','79','80'];//h2 18 m 19 24 12 11 14 新加O3 B8帐号,2016-12-5 WZ
	var filterAccountID = <?php echo $overseaAccount;?>;
	//console.log(filterAccountID);
	for(index in accountIds){
		var accountId = accountIds[index];
		//console.log(accountId);
		//console.log($.inArray(accountId, filterAccountID));
		if($.inArray(accountId, filterAccountID) >= 0){
			continue;
		}
		var price = getMaxSalePrice(accountId, listingType);
		
		if(price >= 5){
			$("#domestic_"+accountId).find("select").find("option[value='ePacketChina']").attr("selected", true);
		}else{
			$("#domestic_"+accountId).find("select").find("option[value='EconomyShippingFromOutsideUS']").attr("selected", true);
		}
	}
	
}

/**
 * @desc 获取最大销售价
 */
function getMaxSalePrice(accountId, listingType){
	var maxPrice = 0;
	if(listingType == 3){
		//多属性
		var salePriceGroup = $("#variation_list .price_info").filter(function(){
			return $(this).attr("accountid") == accountId;
		});
		
		salePriceGroup.each(function(i, n){
			if(maxPrice < $(n).val()){
				maxPrice = $(n).val();
			}
		});
	}else{
		//一口价或者拍卖
		maxPrice = $("#sale_price_"+accountId).val();
	}
	return maxPrice;
}

/**
 * @desc 获取最小的运费
 */
function getMinShipCost(accountId){
	var minShipCost = -1;

	$(".ebay_shipcost_"+accountId).each(function(i, e){
		var shipcost = $(e).val();
		if(minShipCost == -1){
			minShipCost = shipcost;
		}else if(shipcost < minShipCost){
			minShipCost = shipcost;
		}
	});
	if(minShipCost<0) minShipCost = 0;
	return minShipCost;
}

//更改当前的状态(当前操作的分类,选择的分类,则checkbox的状态) next为是否自动到下一个分类
function changeCurrentStatus(next){
	//改变当前操作分类
	
}

/**
 * 手动同步分类树
 */
function syncCategoryList(obj){
	if(!confirm("同步分类会花费很长一段时间，您确定需要同步吗？")){
		return;
	}
	var siteID = $(obj).attr("site-id");
	$.ajax({
		type: "GET",
		url: "<?php echo Yii::app()->request->baseUrl;?>/ebay/ebaycategory/getcategory",
		data: "site_id="+siteID,
		dataType:'json',
		success: function(result){
			if(result.statusCode == 200){
				navTab.reload();
			}else{
				alertMsg.error(result.message);
			}
		}
	});
	
}
//保存刊登数据
function saveInfo(){
	$.ajax({
			type: 'post',
			url: $('form#ebay_product_add').attr('action'),
			data:$('form#ebay_product_add').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					$('form#ebay_product_add a.display_list').click();
					navTab.closeTab(result.navTabId);
				}
			},
			dataType:'json'
	});
}

//修改了attr
function changeAttrVal(obj){
	var maxLength = 65;
	var attrLength = $(obj).val().length;
	if(attrLength>maxLength){
		var msg = '<font color="red">属性值已超出<b>'+(attrLength-maxLength)+'</b>个字符</font>';
	}else{
		var msg = '<font color="green">属性值还可以输入<b>'+(maxLength-attrLength)+'</b>个字符</font>';
	}
	$(obj).parent().siblings(".attrv_info").html(msg);
}

//修改了title
function changeTitleValue(obj){
	var maxLength = 80;
	var titleLength = $(obj).val().length;
	if(titleLength>maxLength){
		var msg = '<font color="red">已超出<b>'+(titleLength-maxLength)+'</b>个字符</font>';
	}else{
		var msg = '<font color="green">还可以输入<b>'+(maxLength-titleLength)+'</b>个字符</font>';
	}
	$(obj).parent().find(".title_info").html(msg);
}
</script>

<script type="text/javascript">
var _keywords = $('input[name=search_keywords]').val();
$(function(){
	$('ul.tabHeaderList li').click(function(){
		changeTab(this);
	});
	<?php if ($action != 'update') { ?>
	$('select[name=category_list_history]').change();
	<?php } ?>

	KindEditor.create('textarea.ebayproductDescription',{
		allowFileManager: true,
		filterMode: false,
		width: '100%',
		height: '550',
		newlineTag:'br',
		afterCreate : function() {
	    	this.sync();
	    },
	    afterBlur:function(){
	        this.sync();
	    },
	});
});
</script>

<script type="text/javascript">



var shippinginfo = "";
var specialCountry = "";
var servicelNum = 0;//运输序号
var locationNum = 0;//国家及地区序号

//获取国家和地区
function getLocations(accountId, shiptolocation){
	var locations = shiptolocation.split(',');
	var html = "";
	try{
		$.each(shippinginfo.ShippingLocationDetails,function(i,item){
			if(item=='None' || item=='Worldwide'){
				return;
			}
			var checked = $.inArray(i,locations)!=-1 ? 'checked' : '';
			locationNum++;
			html += "<li>"
						+"<label><input type='checkbox' class='location' "+checked+" name='locations["+accountId+"]["+servicelNum+"]["+locationNum+"]' title='"+item+"' value='"+i+"'>"
						+""+item+"</label>"	
					+"</li>";
		});
	}catch(e){}
	return html;
}
function getShiptoInfo(accountId, shiptolocation){
	var html = "<tr><td>"
		+"SHIP TO:"
		+"<select name='shoptos["+accountId+"]["+servicelNum+"]' onchange='selectLocation(this);'>"
			+"<option value='Worldwide'>Worldwide</option>"
			+"<option value='' "+(shiptolocation!='Worldwide' ? 'selected':'')+">Choose custom location</option>"
		+"</select> "
		+"<ul id='locations' "+(shiptolocation!='Worldwide'?'style=\"display:block\"':'')+">"+getLocations(accountId, shiptolocation)+"</ul>"
	+"</td></tr>"
	return html;
}

//获取运输方式
function getServices(accountId, type,service){
	var services = type=='international' ? shippinginfo.InternationalServices : shippinginfo.DomesticServices;
	var html = "<select name='services["+accountId+"]["+servicelNum+"]' dataType='Require' msg='必选'>";
	html += "<option value=''>请选择</option>";
	try{
		$.each(services,function(i,item){
			html += "<option disabled value=''>"+i+" Service</option>";
			$.each(item,function(key,val){
				html += "<option value='"+key+"' "+(service==key?'selected':'')+">&nbsp;&nbsp;&nbsp;"+val+"&nbsp;</option>";
			});
		});
		html += "</select>";
	}catch(e){}
	return html;
}
		
//添加运输
//service,costtype,additionalcost,shiptolocation
function addShippingService(accountId, type, service,costtype,additionalcost,shiptolocation, shipcost, additionalshipcost){
	//console.log('xxxxxxx');
	//Nick 2013-8-27添加特殊国家
	 var insert = '';
	 var scountry = specialCountry;
	 var countryArr = scountry.split(',');
	 var length = countryArr.length;
	 for(var i=0;i < length;i++){
	     insert += "<option value='"+countryArr[i]+"' "+(costtype== countryArr[i] ?'selected':'')+">"+countryArr[i]+"</option>";
	 }
	var servicename = type=='international' ? '国际运输' : '本地运输';
	var max = type=='international' ? 5 : 4;
	var selectShipto = type=='international' ? true : false;

	if(service==undefined){ service = '';}
	if(costtype==undefined){ costtype = '';}
	if(additionalcost==undefined){ additionalcost = '0.05';}
	if(shiptolocation==undefined){ shiptolocation = 'Worldwide';}

	if($("#"+type+"_"+accountId+" table").size()>=max){
		alert(servicename+"选项不能超过"+max+"个");
		return;
	}
	servicelNum++;
	var html = "<table style='width:600px;margin-bottom:15px;border:1px solid #ccc;padding:5px;' class='tb_trbl'>"
		 			+"<tr><td scope='col'></td></tr>"
		  			+ (selectShipto ? getShiptoInfo(accountId, shiptolocation) : '')
  					+"<tr><td scope='col'>"
  						+"Services: "+getServices(accountId, type,service)
    				+"</td></tr>"
    				/* +"<tr><td scope='col'>"
    					+"Cost:"
    					+"<span>"
    					+"<select name='costtypes["+accountId+"]["+servicelNum+"]' onchange='changeReferValue(this)'>"
    						+"<option value='1' "+(costtype=='1'?'selected':'')+">FREE SHIPPING</option>"
	    						+"<option value='2' "+(costtype=='2'?'selected':'')+">EUB</option>" 
	                   		 +insert+
	                    "</select> "
						+"</span>"
					+"</td></tr>"
					+"<tr><td scope='col'>"
						+"Additional = Cost-" 
						+"<input name='additionalcosts["+accountId+"]["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+additionalcost+"' " 
						+"dataType='Double' Require='true' msg='必须输入数值' id='additionalcost"+servicelNum+"'> "
						+"<span><input type='button' onClick='removeShippingService(this);' value='删除'/></span>"
					+"</td></tr>" */

					+"<tr><td scope='col'>"
 					+"Cost:"
 						+"<span>"
 						+"<input class='ebay_shipcost_"+accountId+"' name='shipcost["+accountId+"]["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+shipcost+"'>"
						+"</span>"
					+"</td></tr>"
					+"<tr><td scope='col'>"
						+"Additional:" 
						+"<input name='additionalshipcost["+accountId+"]["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+additionalshipcost+"' " 
						+"dataType='Double' Require='true' msg='必须输入数值' id='additionalcost"+servicelNum+"'> "
						+"<span><input type='button' onClick='removeShippingService(this);' value='删除'/></span>"
					+"</td></tr>"
					+"<input type='hidden' name='shippingServices["+accountId+"]["+servicelNum+"]' value='"+type+"'/>"
				+"</table>";
	//console.log(html);
	$("#"+type+"_"+accountId).append(html);
	//console.log($("#"+type+"_"+accountId));
}
//删除一个国际运输方式
function removeShippingService(obj){
	$(obj).parent().parent().parent().parent().parent().remove();
}
//选择国家
function selectLocation(obj){
	if(obj.value=='Worldwide'){
		$(obj).parent().find("#locations").hide(0);
	}else{
		$(obj).parent().find("#locations").show(0);
	}
}

function getShippingTemplate(siteId, configType, addId){
	var accountIds = $("input[name='accountids']").val();
	var accountIdsArr = accountIds.split(',');
	addId = addId ? addId : '';
	var url = "<?php echo Yii::app()->createUrl('/ebay/ebayproductadd/getshipping')?>";
	var param = {account_id:accountIds, site_id:siteId, config_type:configType, add_id:addId};
	$.post(url, param, function(data){
		if(data.statusCode == 200){
			specialCountry = data.specialCountry;
			shippinginfo = data.shippingInfo;
			$.each(data.shippingTemplate, function(accountId, templates){
				$("#domestic_"+accountId).html("");
				$("#international_"+accountId).html("");
				$.each(templates, function(n, template){
					var type = template.shipping_type == 1 ? 'domestic' : 'international';
					addShippingService(accountId, type, template.shipping_service, template.cost_type, template.additional_cost, template.ship_location, template.ship_cost, template.additional_ship_cost);
				});
			});
		}
		//为了把运费统计进去
		//console.log("========22222222=======");
		updateConfirmContent();
	}, 'json');
}

$("#ebay_product_add_config_type").change(function(){
	var configType = $(this).val();
	var siteId = $("input[name='listing_site']").val();
	var addId = $("input[name='add_id']").val();
	getShippingTemplate(siteId, configType, addId);
});
$("#ebay_product_add_config_type").trigger("change");



// -------------------
$(".ebayztimgs .extra_checked").die('click');
$(".ebayztimgs .extra_checked").live('click', function(event){
	var checked = !!$(this).attr("checked");
	if(checked){
		if($(".ebayztimgs .extra_checked:checked").length>12){
			alert('主图最大选择12个!');
			return false;
		}
	}
	event.stopPropagation();
});
$(".ebayftimgs .extra_checked").die('click');
$(".ebayftimgs .extra_checked").live('click', function(event){
	 event.stopPropagation();
});
$(".ebayztimgs .extra_checked, .ebayftimgs .extra_checked").mousedown(function(event){
	 event.stopPropagation();
});

// -------------------

</script>