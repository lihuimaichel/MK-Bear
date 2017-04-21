<style>
<!--
/* #product_add .pageFormContent label{width:auto;} */
#product_add table td li{line-height:20px;}
#product_add table td font.bold{font-weight:bold;}
#product_add table.dataintable_inquire td td{border:none;}
#product_add .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.productAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
/* #product_add table{display:inline-block;} */
#variationTbody tr td input.text_price{width:90px;}
#variationTbody tr td input.text_sku{width:120px;}
#variationTbody tr td select{width:230px;}
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
	         <strong>SKU：[<?php echo $listingProduct['sku'];?>]</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
	       <div class="row productAddInfo" style="width:99%;float:left;">
	       <?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'product_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('lazada/lazadaproductadd/saveData'), 
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
    				                <li><font class="bold">SKU：</font><?php echo 
                                  		CHtml::link($listingProduct['sku'], '/products/product/viewskuattribute/sku/'.$listingProduct['sku'], 
                    			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
                    			?></li> 
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font><?php echo $listingParam['listing_type']['text'];?></li>
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Mode')?>：</font><?php echo $listingParam['listing_mode']['text'];?></li>
				                    <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Site')?>：</font><?php echo $listingParam['listing_site']['text'];?></li>
				                </ul>
				                <input type="hidden" name="baseInfo[sku]" value="<?php echo $listingProduct['sku'];?>" />
				                <input type="hidden" name="baseInfo[listing_type]" value="<?php echo $listingParam['listing_type']['id'];?>" />
				                <input type="hidden" name="baseInfo[listing_mode]" value="<?php echo $listingParam['listing_mode']['id'];?>" />
				                <input type="hidden" name="baseInfo[listing_site]" value="<?php echo $listingParam['listing_site']['id'];?>" />
				            </td>
				        </tr>
				        <!-- 刊登参数显示END -->
				        
				        <!-- 类别显示START -->
				        <tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Category');?></td>
				            <td>
				                <table class="category">
				                    <tr>
				                        <td>
				                            <div>
				                                <input type="radio" name="get_category" id="history_category" disabled="disabled" />
				                                <label for="history_category"><?php echo Yii::t('lazada', 'History Category');?></label>
				                                <a style="display:none;" href="<?php echo Yii::app()->createUrl('lazada/lazadacategory/historycategory', array(
				                                    'sku' => $listingProduct['sku']
				                                ));?>" lookupGroup="" lookupPk="category_id" width="400" height="300" ><?php echo Yii::t('lazada', 'History Category');?></a>
				                                <div style="clear:both;"></div>
				                            </div>
				                            <div>
				                                <input type="radio" name="get_category" id="choose_category" />
				                                <label for="choose_category"><?php echo Yii::t('lazada', 'Choose Category');?></label>
				                                <a style="display:none;" href="<?php echo Yii::app()->createUrl('lazada/lazadacategory/categorytree');?>" 
				                                lookupGroup="" lookupPk="category_id" width="400" height="300" ><?php echo Yii::t('lazada', 'Choose Category');?></a>
				                                <div style="clear:both;"></div>
				                            </div>
				                        </td>
				                        <td style="vertical-align:middle;">
				                            <div id="lazada_category_name" style="font-weight:bold;font-size:14px;"></div>
				                            <input type="hidden" name="category_name" value="" />
				                            <input type="hidden" name="category_id" value="" />
				                        </td>
				                    </tr>
				                </table>
				            </td>
				        </tr>
				        <!-- 类别显示END -->

                        <!-- 产品属性显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;">产品属性</td>
                            <td><?php
                                foreach ($listingAttribute as $key2 => $val2) {     
                                    $attribute_value_name_cn = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($val2,CN);
                                    if ( isset($selectAttrPairs[3]) && in_array($key2, (array)$selectAttrPairs[3]) ) {
                                        $flag = true;
                                        echo CHtml::checkBox("attr[3][]", $flag, array( 'value' => $key2));
                                        echo $attribute_value_name_cn;
                                    }     
                                 }
                                
                            ?></td>
                        </tr>
                        <!-- 产品属性显示END -->
				        
				        <!-- 品牌显示START -->
				        <tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('common', 'Product Brand');?></td>
				            <td>
				                <input class="textInput" name="brand" type="text" size="30" value="VAKIND">
				                <a class="btnLook" width="400" height="300" href="lazada/lazadabrand/list/target/dialog" lookupGroup="" lookupPk="brand"><?php echo Yii::t('lazada', 'Choose Brand On Lazada')?></a>
				            </td>
				        </tr>
				        <!-- 品牌显示END -->
				        <!-- 多属性显示START -->
                                        <tr id="mutilVariationRow" style="display:none">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada_product', 'Sale Variation');?></td>
				            <td>
				                <div id="skuAttributes">
				                <table width="100%" table-layout="fixed" id="skuAttributeTable" class="attributesTable">
                                                    <thead>
                                                        <tr>
                                                            <th id="variationName"></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Size');?></th> 
                                                            <th><?php echo Yii::t('lazada_product', 'Sku');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale Price');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Special Price');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale Start Date');?></th>
                                                            <th><?php echo Yii::t('lazada_product', 'Sale End Date');?></th>
                                                            <th>remove this row</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="variationTbody">
                                                        <tr id="addVariationTd">
                                                            <td colspan="7"><input type="button" value="Add another product variation" onclick="addNewVariation();"></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
				                </div>
				                <div id="productVariations">
				                </div>
				            </td>
				        </tr>
                                        <!-- 多属性显示END -->
				        <!-- 基本信息显示START -->
                        <tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Base Info');?></td>
				            <td>
				                <div class="tabs"> 
	                                <div class="tabsHeader"> 
	 		                            <div class="tabsHeaderContent"> 
                            	 			<ul> 
                            	 			    <?php foreach($listingParam['listing_account'] as $k=>$account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['seller_name'];?>&nbsp;&nbsp;</span></a>
                            	 				</li>
                            	 				<?php endforeach;?>
                            	 			</ul> 
                            	 		</div> 
                            	 	</div>
                            	 	<div class="tabsContent"> 
                            	 	    <?php foreach($listingParam['listing_account'] as $k=>$account):?>
                            	 	    <div class="pageFormContent" style="border:1px solid #B8D0D6">
                             			    <div class="row">
                             			        <?php echo CHtml::label(Yii::t('lazada', 'Sale Price'), 'sale_price_'.$account['id']); ?>
                             			        <?php echo CHtml::textField('baseInfo[sale_price]['.$account['id'].']','',array(
                             			            'id'            => 'sale_price_'.$account['id'],
                             			            'account_id'    => $account['id'],
                             			            'placeholder'   => Yii::t('lazada','Need Specific Category'),
                             			            'disabled'      => 'disabled',
                             			            'onBlur'        => 'loadPriceInfo(this)',
                             			        )); ?>
                             			        <span class="profitDetail"><?php //添加卖价详情说明?></span>
                            	            </div>
                            	            <!-- <div class="row">
                            	            	<?php echo CHtml::label(Yii::t('lazada', 'Discount Scheme'), 'discount_scheme');?>
                            	            	<?php echo CHtml::dropDownList('baseInfo[discount_scheme][' . $account['id'] . ']', '', $listingParam['promotion_list'], array(
                            	            		'empty' => Yii::t('system', 'Please Select'),
                            	            	));?>
                            	            </div> -->
                            	            <div class="row">
                             			        <?php echo CHtml::label(Yii::t('lazada', 'Title'), 'title_'.$account['id']); ?>
                             			        <?php echo CHtml::textField('baseInfo[title]['.$account['id'].']',$listingProduct['skuInfo']['title']['english'],array('id' => 'title_'.$account['id'], 'size' => '100', 'onKeyDown' => 'checkStrLength(this,255)')) ?>
                            	                &nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                            	            </div>
                            	            <div class="row">
                            	       			<?php echo CHtml::label(Yii::t('lazada', 'Product Description'), 'description_'.$account['id']);?>
				                				<textarea rows="42" cols="22" name="baseInfo[description][<?php echo $account['id'];?>]" class="productDescription"><?php echo $listingProduct['skuInfo']['description']['english'];?></textarea>
                            	            </div>
                            	            <div class="row">
                            	       			<?php echo CHtml::label(Yii::t('lazada', 'Product Highlight'), 'highlight_'.$account['id']);?>
				                				<textarea rows="42" cols="22" name="baseInfo[highlight][<?php echo $account['id'];?>]" class="productHightlight"></textarea>
                            	            </div>
                            	            <input type="hidden" class="accountid" name="baseInfo[account][<?php echo $account['id'];?>]" value="<?php echo $account['id'];?>" />
                             			</div>
                            	 		<?php endforeach;?>
                             	    </div>
                             	</div>
				            </td>
				        </tr>
                        <!-- 基本信息显示END -->
                        
                        <!-- 图片信息显示START -->
                        <tr>
                            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Image Info');?></td>
                            <td>
                                <div class="page unitBox">
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php $count = 0;?>
                                        <?php foreach($listingProduct['skuImg']['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="lazada_image">
                                            <img alt="<?php echo $k;?>" src="<?php echo $image;?>" style="width:80px;height:80px;" />
                                            <input type="hidden" value="<?php echo $count;?>" name="imageInfo[sortImg][<?php echo $k;?>]" />
                                        </div>
                                        <?php $count++;?>
                                        <?php endforeach;?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
                            </td>
                        </tr>
                        <!-- 图片信息显示END -->
                        
                        <!-- 属性信息显示START -->
                        <tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Attribute Info');?></td>
				            <td>
				                <div id="lazada_attributes"></div>
				            </td>
				        </tr>
                        <!-- 属性信息显示END -->
				    </tbody>
				</table>
				<div class="formBar">
                    <ul> 
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent"> 
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('lazada', 'Save Into List');?></a>&nbsp;
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="buttonActive" style="margin-left:20px;">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)">
                                        <?php echo Yii::t('lazada', 'Upload Now')?>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('lazada/lazadaproductadd/list/sku/'.$listingProduct['sku'].'/status/'.LazadaProductAdd::UPLOAD_STATUS_DEFAULT);?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('common','Product Add List');?></a>
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
                -->
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">
	KindEditor.create('textarea.productDescription',{
		allowFileManager: true,
		width: '65%',
		height: '400',
		afterCreate : function() {
	    	this.sync();
	    },
	    afterBlur:function(){
	        this.sync();
	    },
	});
	KindEditor.create('textarea.productHightlight',{
		allowFileManager: true,
		width: '65%',
		height: '400',
		afterCreate : function() {
	    	this.sync();
	    },
	    afterBlur:function(){
	        this.sync();
	    },
	});	
    $(function(){
        var page = $('#navTab ul.navTab-tab li.selected').attr('tabid');
    	$('.display_list').attr('rel',page);
    });
    //分类选择
	$('input[name="get_category"]').click(function(){
		var id = $(this).attr('id');
		switch (id){
			case 'choose_category':
			case 'history_category':
				$(this).parent().find('a').click();
				break;
		}
	});

	//图片排序号
    function doAreaSort(obj, everyDivIds, everyDivPos){
        $('.sortDragShow .lazada_image').each(function(){
			var index = $(this).index();
			$(this).find('input').val(index);
        });
        return false;
    }

    function loadFillInfoByCategory(){
		//获取分类ID
		var categoryID = parseInt($('input[name="category_id"]').val());
		var listing_type = parseInt($('input[name="baseInfo[listing_type]"]').val());
                
		var accounts = [];
		$('.accountid').each(function(){
			accounts.push($(this).val());
		});
		
		if( categoryID > 0 ){
			//加载分类类容
			$.ajax({
				type:'post',
				url:'lazada/lazadaproductadd/confirmcategory',
				data:{category_id:categoryID, sku:'<?php echo $listingProduct['sku'];?>', accounts:accounts.join(), site_id:'<?php echo $listingParam['listing_site']['id'];?>', listing_type:listing_type},
				success:function(result){
                                    $('#lazada_category_name').html(result.categoryName);//展示分类名

                                    //result.priceDetail 加载卖价 
                                    if (result.priceDetail != undefined ) {
                                        $.each(result.priceDetail,function(i,item){
                                                $('#sale_price_'+i).val(item.salePrice);
                                                $('#sale_price_'+i).removeAttr('disabled');
                                                $('#sale_price_'+i).next('.profitDetail').html('&nbsp;&nbsp;<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+item.profit+',<?php echo Yii::t('common', 'Profit Rate');?>:'+item.profitRate+'.</font>'
                                                                +'<a href="javascript:;" onClick="alertMsg.confirm(\''+item.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
                                        });
                                    }

                                    var size_multi_variation = false;
                                    var list_type = '<?php echo LazadaProductAdd::LISTING_TYPE_VARIATION; ?>';
                                    if(listing_type == list_type ){
                                        //多属性产品读取子sku
                                        var variationName = 'Variation';
                                        var variation_html = '';
                                        var variation_type = 'input';
                                        var type_check = false;
                                        var first_row = true;
                                        $('#variationTbody').find("tr[type='add']").remove();

                                        //result.variation_info
                                        if (result.variation_info != undefined ) {
                                                $.each(result.variation_info, function(j,detail){
                                                //result.attributes
                                                if(result.attributes != undefined && type_check == false){
                                                    //判断多属性显示类型，input为文本框，list为列表 
                                                    $.each(result.attributes,function(i,item){
                                                        if( item.Name == 'size' && item.FeedName == 'Variation' ){
                                                            variation_type = 'list';
                                                            variationName = item.Name;
                                                            return false;
                                                        }
                                                    });
                                                    type_check = true;
                                                }

                                                variation_html += '<tr type="add">';

                                                if(variation_type == 'input'){
                                                    variation_html += '<td><div id="multiVariation"><input  style="line-height:20px;font-size:20px"  type="text" name="variationValue[]" value="' + detail.input_value + '"/></div></td>';
                                                } else {
                                                    //result.attributes 属性自动匹配
                                                    if (result.attributes != undefined ) {
                                                        $.each(result.attributes,function(i,item){
                                                            if( item.Name == 'size' && item.FeedName == 'Variation' ){
                                                                size_multi_variation = true;
                                                                variation_html += '<td><select id="variationlist" name="variationValue[]">';
                                                                variation_html += '<option value="">Please Select</option>';
                                                                //item.Options.Option
                                                                if (item.Options.Option != undefined ) {
                                                                    $.each(item.Options.Option,function(k,val){
                                                                        if(detail.list_value == val.Name){
                                                                            val.isDefault = 1;
                                                                        } else {
                                                                            val.isDefault = 0;
                                                                        }
                                                                        variation_html += '<option '+(val.isDefault==1 ? 'selected="selected"' : '')+'>'+val.Name+'</option>';
                                                                    });
                                                                }

                                                                variation_html += '</select></td>';
                                                            }
                                                        });
                                                    }
                                                }

                                                //尺寸
                                                variation_html += '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationSize[]" value=""/></td>';

                                                //子sku自动填充
                                                variation_html += '<td><input style="line-height:20px;font-size:20px" type="text"  class="text_sku"  name="variationSku[]" value="' + detail.sku + '" /><input type="hidden" name="variationColor[]" value="' + detail.color_id + '"/></td>';                                                                                        
                                                //价格、卖价、销售开始日期、销售截止日期自动填充
                                                variation_html += '<td><input type="text" class="text_price"  style="line-height:20px;font-size:20px"  name="variationPrice[]" value="' + detail.price + '"/></td>';
                                                variation_html += '<td><input type="text"  class="text_price"   style="line-height:20px;font-size:20px"  name="variationSalePrice[]" value="' + detail.sale_price + '"/></td>';
                                                variation_html += '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceStart[]" value="' + detail.sale_price_start + '"/></td>';
                                                variation_html += '<td><input type="text"  style="line-height:20px;font-size:20px"  name="variationSalePriceEnd[]" value="' + detail.sale_price_end + '"/></td>';
                                                if(first_row == false){
                                                    variation_html += '<td><input type="button" onclick="removeVariation(this);" value="remove this row" /></td>';
                                                } else {
                                                    variation_html += '<td><input type="button" style="display:none" onclick="removeVariation(this);" value="remove this row" /></td>';
                                                }
                                                variation_html += '</tr>';
                                                
                                                $('#addVariationTd').before(variation_html);
                                                
                                                first_row = false;
                                                //重置变量
                                                variation_html = '';
                                            });
                                        }

                                        $('#mutilVariationRow').show();
                                        $('#variationName').html('<input type="hidden" name="variationName" value="' + variationName + '">' + variationName); 
                                    }				
                                    //加载属性(读取产品管理预存属性)
                                    renderAttribute(result.attributes, size_multi_variation);
				},
				dataType:'json'
			});
		}else{
			alertMsg.error('Can Not Get Category!');
		}
    }

    //显示属性
    function renderAttribute(attributes, size_multi_variation){
    	var html = '';
        if(attributes!=undefined ){
        	$.each(attributes,function(i,item){
                //size在上面多属性的时候，下面不显示size
                if(item.name == 'size' && size_multi_variation == true){
                    return true;
                }
            	//加载选项
            	var valHtml = '';
                var display_attribute = true;
            	switch(item.inputType){
            		case 'singleSelect':
                               if('Option' in item.options){
                                    valHtml += '<select id="'+item.name+'" name="attributeInfo['+item.name+']">';
                                    valHtml += '<option value="">Please Select</option>';
                                    if (item.options.Option != undefined) {
                                        $.each(item.options.Option,function(k,val){
                                                valHtml += '<option '+(val.isDefault==1 ? 'selected="selected"' : '')+'>'+val.name+'</option>';
                                        });
                                    }
                                    valHtml += '</select>';
                                } else {
                                    display_attribute = false;
                                }
                		break;
            		case 'text':
                        var valueText = '';
                        if(item.name=='model'){
                            valueText = $('.tabsHeaderContent .selected a span').text();
                            valueText = $.trim(valueText);
                            valueText += '<?php echo '-'.$listingProduct['sku'];?>';
                        }
            			valHtml += '<input type="text" name="attributeInfo['+item.name+']" value="'+valueText+'" />';
                		break;
                    case 'numeric':
                        valHtml += '<input type="text" name="attributeInfo['+item.name+']" />';
                        break;
                    case 'richText':
                        var valueText = '';
                        if(item.name=='warranty_type'){
                            valueText = 'No Warranty';
                        }
                        valHtml += '<input type="text" name="attributeInfo['+item.name+']" value="'+valueText+'" />';
                        break;
            		case 'multiSelect':
            			valHtml += '<div style="float:left;width:900px;"><ul class="multi_select">';
                        if (item.options.Option != undefined ) {
                            $.each(item.options.Option,function(k,val){
                                valHtml += '<li>'
                                    + '<input name="attributeInfo['+item.name+']['+val.name+']" type="checkbox" '+(val.isDefault==1 ? 'checked="checked"' : '')+' value="'+val.name+'" id="'+item.name+'_'+val.name+'" />'
                                    + '<label for="'+item.name+'_'+val.name+'">'+val.name+'</label>';
                                if( item.name=='color_family' ){
                                        valHtml += '<div style="display:inline-block;border:1px #ccc solid;width:10px;height:10px;background:'+val.name+';"></div>';
                                }
                                valHtml += '</li>';
                            });
                        }
            			valHtml += '</ul></div>';
                		break;
            	}
                if(display_attribute == true){
                    //加载属性条目
                    html+= '<div class="row">'
            			+ '<label for="'+item.name+'">'+item.label+(item.isMandatory==1 ? '<span class="required">*</span>' : '')+'</label>'
        				+ valHtml
        				+ '<span class="attributeDoc" style="line-height:26px;margin-left:10px;">'+(typeof(item.Description)=='object' ? '' : item.Description)+'</span>'
        				+ '</div>';
                }
    		});
        }

		$('#lazada_attributes').html(html);
    }
    
    //计算利润信息
    function loadPriceInfo(self){
    	$.ajax({
			type:'post',
			url:'lazada/lazadaproductadd/getpriceinfo',
			data:{
				category_id:parseInt($('input[name="category_id"]').val()), 
				sku:'<?php echo $listingProduct['sku'];?>', 
				account_id:$(self).attr('account_id'),
				price:$(self).val(),
				site_id:'<?php echo $listingParam['listing_site']['id'];?>'
			},
			success:function(result){
				$(self).next('.profitDetail').html(
						'<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+result.profit
						+',<?php echo Yii::t('common', 'Profit Rate');?>:'+result.profitRate+'.</font>'
						+'<a href="javascript:;" onClick="alertMsg.confirm(\''+result.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
			},
			dataType:'json'
		});
    }

    //保存刊登数据
    function saveInfo(){
    	var data = $('form#product_add').serializeArray();
		$.ajax({
				type: 'post',
				url: $('form#product_add').attr('action'),
				data: data,
				success:function(result){
					if(result.status==0){
						alertMsg.error(result.message);
					}else{
						$('form#product_add a.display_list').click();
					}
				},
				dataType:'json'
		});
    }
    
	//检测字符长度
    function checkStrLength(self, max){
		var length = $(self).val().length;
		var remain = parseInt(max) - parseInt(length);
		if( remain >= 0 ){
			$(self).next('span.warn').html(remain+' <?php echo Yii::t('common','Char Left')?>');
		}else{
			$(self).val($(self).val().substr(0,max))
		}
    }
    
    //添加新的属性
    function addNewVariation(){
        var newVariationRow = $('#variationTbody tr:first').clone();
        newVariationRow.find('input:text').val('');
        newVariationRow.find('select').prop('selectedIndex', 0);
        newVariationRow.find('input:button').show();
        $('#addVariationTd').before(newVariationRow);
    }
    
    //删除多属性的行
    function removeVariation(removeButton){
        var variationRow = $(removeButton).parent().parent();
        variationRow.remove();
    }
</script>