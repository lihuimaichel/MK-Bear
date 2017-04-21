<style>
<!-- 
.pageFormContent label{
	display: inline;
    float: none;
    width: auto;
}
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
#product_add table.dataintable_inquire table.variationProductTable {
	min-width:100px;
	padding:0;
	margin:10px auto;
	align:center;
	border-width:0 0 0 1px;
	border-color:#888888;
	border-style:solid;
}
#product_add table.dataintable_inquire table.variationProductTable th, #product_add table.dataintable_inquire table.variationProductTable td {
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
	display:block;
}

div.ftimgs .extra_checked{
	display:block;
}
.descriptionContent label{
	width: 120px;
	float: left;
	padding: 0 5px;
	line-height: 21px;
}
-->
</style>
<?php 
    $updateID = Yii::app()->request->getParam('id');
    if (isset($updateID) && !empty($updateID)){
        foreach ($accountList as $accountID => $account){
            $accountsUpdate[] = $accountID;
        }
    }
?>
<script type="text/javascript">
var _selectedAttributes = new Array();		//选择的sku属性
var _skuAttributes = new Array();
var _publishType = <?php echo $publishParams['publish_type']['id'];?>;
var _variationSkus = new Array();
var _selectedSkuMap = new Array();
//AjunLongLive! 2017 03 02  判断是否绑定分类情况
var canSubmitStatus = true;
var notSubmitReason = '';
var categoryErrorMsg = '';
var accountNums = <?php echo count(isset($accountsUpdate) ? $accountsUpdate : Yii::app()->request->getParam('accounts')); ?>;
var usersJson = '<?php echo json_encode(isset($accountsUpdate) ? $accountsUpdate : Yii::app()->request->getParam('accounts'));  ?>';

<?php 
if (isset($variationAttributes) && !empty($variationAttributes)) {
	foreach ($variationAttributes as $skuAttribute) {
		$key = 0;
		foreach ($skuAttribute['aeopSKUProperty'] as $attributes) {
			$key += $attributes['propertyValueId'];
		}
		$propertyValueDefinitionName = isset($skuAttribute['aeopSKUProperty'][0]['propertyValueDefinitionName']) ? $skuAttribute['aeopSKUProperty'][0]['propertyValueDefinitionName'] : '';
		echo '_variationSkus[' . $key . '] = new Array();' . "\n";
		echo '_variationSkus[' . $key . '].sku = \'' . $skuAttribute['skuCode'] . '\'' . "\n";
		echo '_variationSkus[' . $key . '].price = \'' . $skuAttribute['skuPrice'] . '\'' . "\n";
		echo '_variationSkus[' . $key . '].customName = \'' . $propertyValueDefinitionName . '\'' . "\n";
	}
}
?>
//设置选中分类
function setCategory(obj, t) {
	clearnSelectedCategory();
	clearnAttributes();
	var text;
	if (typeof(t) != 'undefined')
		text = t;
	else
		text = $(obj).find('option:selected').text();
	$('input[name=category_name]').val(text);
	$('input[name=category_id]').val($(obj).val());
}
//清除选择的分类
function clearnSelectedCategory() {
	clearnAttributes();
	$('input[name=category_name]').val('');
	$('input[name=category_id]').val(0);
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

//根据关键词搜索产品分类
function searchKeywords() {
 	var keywords = $('input[name=search_keywords]').val();
	if (keywords == '' || keywords == _keywords) {
		alertMsg.error('<?php echo Yii::t('aliexpress_product', 'Please Input Keywords');?>');
		return false;
	}
	$('select[name=category_list_search]').empty();
	var url = '/aliexpress/aliexpresscategory/getcategorysuggest/keyword/' + keywords;
	$.get(url, function(data){
		if (data != null && data.statusCode == '200') {
			var categoryList = data.categoryList;
			var options;
			for (var i in categoryList) {
				options += '<option value="' + categoryList[i].category_id + '">' + categoryList[i].category_name + '</option>' + "\n";
			}
			$('select[name=category_list_search]').html(options);
		} else {
			alertMsg.error(data.message);
			return false;
		}
	}, 'json');
}
//查找子类别
function findSubCategory(obj) {
	var cateID = $(obj).val();
	if (accountNums != 1){
		//判断分类针对每个用户的绑定情况
		$.ajax({
			type: 'post',
			url: '<?php echo Yii::app()->request->baseUrl;?>/aliexpress/aliexpress_account_bind_category/check_category_bind_status',
			data:{
				categoryID:cateID,
				usersJson:usersJson
			},
			success:function(result){
				//alert(result);
				console.log(result);
				if (result != null && typeof result == 'object'){
					if (result.status == 'failure'){
						console.log(result.msg);
						canSubmitStatus = false;
						var errorMsg = '用户 ';
						for (i in result.msg.unbind){
							//console.log(i);
							//console.log(result.msg.unbind[i]);
							errorMsg += result.msg.unbind[i] + ',';
						}
						errorMsg += '没有绑定' + result.msg.categoryLevel + '级分类“' + result.msg.categoryName + '”';
						notSubmitReason = errorMsg;
						$(obj).nextAll().remove();
						alertMsg.error(errorMsg);
					} else if (result.status == 'success'){
						//alert(result.msg);
						console.log('全部用户均有绑定该分类！');
						canSubmitStatus = true;
						clearnSelectedCategory();	
						var url = 'aliexpress/aliexpresscategory/findsubcategory/category_id/' + cateID;
						$.get(url, function(data){
							$(obj).nextAll().remove();
							if (data != null && data.statusCode == '200') {
								var level = data.level;
								var categoryList = data.category_list;
								var html = '<select size="16" name="category_list_choose_level_' + level + '" onclick="findSubCategory(this)">' + "\n";
								for (var i in categoryList){
									html += '<option value="' + i + '">' + categoryList[i] + '</option>' + "\n";
								}
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
	} else {
		clearnSelectedCategory();	
		var url = 'aliexpress/aliexpresscategory/findsubcategory/category_id/' + cateID;
		$.get(url, function(data){
			$(obj).nextAll().remove();
			if (data != null && data.statusCode == '200') {
				var level = data.level;
				var categoryList = data.category_list;
				var thirdCategoryNotAllStatus = false;
				var html = '<select size="16" name="category_list_choose_level_' + level + '" onclick="findSubCategory(this)">' + "\n";
				//当是第三级，判断是不是全选了
				if (level == 3){
	                if (accountNums == 1){
	                	for (var i in categoryList){
	                		var thirdCategoryReplace = ',' + thirdCategory;
	                    	var thirdCategoryTemp = thirdCategoryReplace.replace(',' + i + ',','');
	                    	if (thirdCategoryTemp.length != thirdCategoryReplace.length){                   		 
	                    		 thirdCategoryNotAllStatus = true;                    		 
	                        	 break;
	                    	}
	        			}
	                }                    
	            }
				console.log('thirdCategoryNotAllStatus:' + thirdCategoryNotAllStatus);
				for (var i in categoryList){
					//var secondCategory = '{$userBindCategory['second_category']}';
	                //var thirdCategory = '{$userBindCategory['third_category']}';
	                if (level == 2 && secondCategory != ''){
	                    if (accountNums == 1){
	                        if (secondCategory != 'all'){
	                            //console.log(',' + i + ',');
	                        	var secondCategoryReplace = ',' + secondCategory;
	                        	var secondCategoryTemp = secondCategoryReplace.replace(',' + i + ',','');
	                        	if (secondCategoryTemp.length ==  secondCategoryReplace.length) continue;
	                        }
	                    }
	                } else if (level == 3 && thirdCategoryNotAllStatus && thirdCategory != ''){
	                    if (accountNums == 1){
	                    	//console.log(',' + i + ',');
	                    	var thirdCategoryReplace = ',' + thirdCategory;
	                    	var thirdCategoryTemp = thirdCategoryReplace.replace(',' + i + ',','');
	                    	if (thirdCategoryTemp.length ==  thirdCategoryReplace.length) continue;
	                    }                    
	                }
					html += '<option value="' + i + '">' + categoryList[i] + '</option>' + "\n";
				}
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
}


//清除分类属性文本
function clearnAttributes() {
	_skuAttributes = new Array;
	_selectedAttributes = new Array();
	$('#skuAttrRow').hide();
	$('#commomAttrRow').hide();
	$('#skuAttributes').empty();
	$('#commonAttributes').empty();
}

//同步该类目下面的分类属性
function syncCategoryAttributes(){
	var cateID = $('input[name=category_id]').val();
	if (cateID == '0')
		return false;
	var url = 'aliexpress/aliexpresscategory/getcategoryattributes/category_id/' + cateID;
	$.get(url, function(data){
		if (data != null && data.statusCode != 200) {
			alertMsg.error(data.message);
			return false;
		}
		findCategoryAttributes();
	}, 'json');
	
}

//同步该类目下面的品牌
function syncCategoryAttributesBrand(){
	var cateID = $('input[name=category_id]').val();
	if (cateID == '0')
		return false;
	var url = 'aliexpress/aliexpresscategory/getcategoryattributes/category_id/' + cateID + '/attribute_id/2';
	$.get(url, function(data){
		if (data != null && data.statusCode != 200) {
			alertMsg.error(data.message);
			return false;
		}
		findCategoryAttributes();
	}, 'json');
	
}
//查找分类下的属性
function findCategoryAttributes() {	
	var cateID = $('input[name=category_id]').val();
	if (cateID == '0')
		return false;
	//判断分类针对每个用户的绑定情况
	$.ajax({
		type: 'post',
		url: '<?php echo Yii::app()->request->baseUrl;?>/aliexpress/aliexpress_account_bind_category/check_category_bind_status',
		data:{
			categoryID:cateID,
			usersJson:usersJson
		},
		success:function(result){
			//alert(result);
			console.log(result);
			if (result != null && typeof result == 'object'){
				if (result.status == 'failure'){
					//console.log(result.msg);
					canSubmitStatus = false;
					var errorMsg = '用户 ';
					for (i in result.msg.unbind){
						//console.log(i);
						//console.log(result.msg.unbind[i]);
						errorMsg += result.msg.unbind[i] + ',';
					}
					errorMsg += '没有绑定' + result.msg.categoryLevel + '级分类“' + result.msg.categoryName + '”';
					notSubmitReason = errorMsg;
					alertMsg.error(errorMsg);
				} else if (result.status == 'success'){
					//alert(result.msg);
					console.log('全部用户均有绑定该分类！');
					canSubmitStatus = true;
					//之前的没有验证是否绑定的代码  start
					clearnAttributes();
					var url = 'aliexpress/aliexpresscategory/findcategoryattributes/category_id/' + cateID + '/sku/<?php echo $publishParams['sku'];?>';
					$.get(url, function(data){
						if (data != null && data.statusCode != '200') {
							alertMsg.error(data.message);
							//获取产品价格
							getProductPrice();
							return false;
						}
						var skuAttributes = data.sku_attributes;
						var commonAttributes = data.common_attributes;
						_selectedSkuMap = data.selected_sku_map;
						//生成sku属性
						var html = '<table id="skuAttributeTable" class="attributesTable"><tbody>' + "\n";
						for (var i in skuAttributes) {
							//将属性保存在变量里面
							_skuAttributes[skuAttributes[i].attribute_id] = {attribute_id:skuAttributes[i].attribute_id,
										attribute_spec:skuAttributes[i].attribute_spec,
										attribute_customized_name:skuAttributes[i].attribute_customized_name,
										attribute_customized_pic:skuAttributes[i].attribute_customized_pic,
										attribute_key_attribute:skuAttributes[i].attribute_key_attribute,
										attribute_required:skuAttributes[i].attribute_required,
										attribute_name: skuAttributes[i].attribute_name_english + '(' + skuAttributes[i].attribute_name_Chinese + ')',
									};
							_skuAttributes[skuAttributes[i].attribute_id].value_list = new Array;
							
							var options = {};
							options.onchange = 'collectSelected(' + skuAttributes[i].attribute_id + ', this);';
							options.name = 'sku_attributes[' + skuAttributes[i].attribute_id + '][]';
							html += '<tr id="attr_' + skuAttributes[i].attribute_id + '">' + "\n" + '<td class="leftColumn">' + "\n";
							html += '<label>' + skuAttributes[i].attribute_name_english + '(' + skuAttributes[i].attribute_name_Chinese + ')' + '：</label>' + "\n";
							html += '</td>' + "\n";
							html += '<td class="rightColumn">' + "\n" + generateHtml(skuAttributes[i], options, 'sku') + "\n" + '<div id="customAttributesBox"></div>' + '</td>' + "\n";
							html += '</tr>' + "\n";
						}
						html += '</tbody></table>' + "\n";
						if (skuAttributes != '') {
							$('#skuAttrRow').show();
							$('#skuAttributes').html(html);
						} else {
						}
						//生成分类属性
						var html = '<table id="commonAttributeTable" class="attributesTable"><tbody style="position:relative;" ><br /><br />' + "\n";
						for (var i in commonAttributes) {
							var searchTd = '';
							var searchHtml = '';
							var searchHtmlButton = '';
							var searchHtmlSelectStyle = '';
							var options = {};
							options.onchange = 'findSubAttributes(this,' + commonAttributes[i].attribute_id + ')';
							options.name = 'common_attributes[' + commonAttributes[i].attribute_id + '][]';
							if (commonAttributes[i].attribute_name_Chinese == '品牌'){
								searchTd = 'ajunBrandSelectTd';
								searchHtml  = '<div style=" background:none; position:absolute;  margin-left:0px; margin-top:-28px; z-index:99;" id="ajunBrandSelectDiv">';
								searchHtml += '<input type="text" style="width:99px;" id="ajunBrandSelectCategory" />';
								searchHtml += '&nbsp;&nbsp;';
								searchHtml += '<input type="button" value=" 搜索品牌 " id="ajunBrandSelectSubmit" />'; 	
								searchHtml += '&nbsp;&nbsp;';
								searchHtml += '<input type="button" value=" 清空 " id="ajunBrandSelectClear" />'; 	
								searchHtml += '</div>';
								options.id  = 'ajunBrandSelect';
								//searchHtmlButton = '&nbsp;&nbsp;&nbsp;<input type="button" value=" 搜索品牌 " id="ajunBrandSelectButton" />';
								searchHtmlSelectStyle = ' style="z-index:66;" ';
							}
				                        html += '<tr id="attr_' + commonAttributes[i].attribute_id + '">' + "\n" + '<td class="leftColumn">' + "\n";
				                        html += '<label>' + generateFlagHtml(commonAttributes[i]) + commonAttributes[i].attribute_name_english + '(' + commonAttributes[i].attribute_name_Chinese + ')' + '：</label>' + "\n";
				                        html += '</td>' + "\n";
				                        html += '<td class="rightColumn ' + searchTd + '" ' + searchHtmlSelectStyle + '>' + searchHtml + "\n" + generateHtml(commonAttributes[i], options, 'common') + searchHtmlButton + '</td>' + "\n";
				                        html += '</tr>' + "\n";          
						}
						html += '</tbody></table>' + "\n";
						if (commonAttributes != '') {
							$('#commomAttrRow').show();
							$('#commonAttributes').append(html);
						}
						console.log(data.selectedSkuAttributes);
						//获取产品价格
						getProductPrice();
						//生成多属性产品
						generateVariationProduct();
						if (($('#ajunBrandSelect').width() - $('#ajunBrandSelectCategory').width()) > 158 ){
							$('#ajunBrandSelectCategory').css('width',$('#ajunBrandSelect').width() - 158);
						}
					}, 'json');
					//之前的没有验证是否绑定的代码  end
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

//生成属性标记html
function generateFlagHtml(data) {
	var html = '';
	if (data.attribute_required == 1)
		html += '<span class="attributeFlag required">*</span>' + "\n";
	if (data.attribute_key_attribute == 1)
		html += '<span class="attributeFlag required">！</span>' + "\n";
	return html;
}
//生成html
function generateHtml(data, options, type) {
	if (data.length <= 0) return false;
	switch(data.attribute_showtype_value) {
		case 'check_box':
			return generateCheckboxHtml(data, options, type);
			break;
		case 'group_item':
			break;
		case 'group_table':
			break;
		case 'input':
			return generateTextInputHtml(data, options, type);
			break;
		case 'interval':
			break;
		case 'list_box':
			return generateDropdownListHtml(data, options, type);
			break;
	}
}
//生成checkbox html
function generateCheckboxHtml(data, options, type) {
	var list = data.value_list;
	var html = '<ul class="attributeValueList">' + "\n";
	for (var i in list) {
		html += '<li>' + "\n";
		var checkedAttribute = '';
		if (type == 'sku') {
			_skuAttributes[data.attribute_id].value_list[list[i].attribute_value_id] = {attribute_value_id:list[i].attribute_value_id,
					attribute_value_name: list[i].attribute_value_en_name + '(' + list[i].attribute_value_cn_name + ')',
					attribute_value_sku: list[i].sku
				};
		}
		if (list[i].selected == 1) {
			checkedAttribute = ' checked="checked"';
			collectSelected(data.attribute_id, list[i].attribute_value_id, true);
		}
		html += '<input children="' + list[i].attribute_children + '" attr_id="' + data.attribute_id + '" id="value_id_' + list[i].attribute_value_id + '" type="checkbox" value="' + list[i].attribute_value_id + '"' + checkedAttribute;		
		for (var j in options) {
			html += ' ' + j + '="' + options[j] + '"';
		}
		html += ' />' + "\n";
		html += '<label for="value_id_' + list[i].attribute_value_id + '">' + list[i].attribute_value_en_name + '(' + list[i].attribute_value_cn_name + ')' + '</label>' + "\n";
		//if (data.attribute_name_english == 'Color')
			//html += '<div style="display:inline-block;border:1px #ccc solid;width:10px;height:10px;background:'+list[i].attribute_value_en_name+';"></div>' + "\n";
		html += '</li>' + "\n";
	}
	html += '</ul>' + "\n";
	return html;
}
//生成dropdown list html
function generateDropdownListHtml(data, options, type) {
	console.log(data);
	var list = data.value_list;
	var html = '<select ';
	for (var j in options) {
		html += ' ' + j + '="' + options[j] + '"';
	}
	html += '>' + "\n";
	html += '<option value=""><?php echo Yii::t('system', 'Please Select');?></option>';
	for (var i in list) {
		var checkedAttribute = '';
		if (type == 'sku') {
			_skuAttributes[data.attribute_id].value_list[list[i].attribute_value_id] = {attribute_value_id:list[i].attribute_value_id,
					attribute_value_name: list[i].attribute_value_en_name + '(' + list[i].attribute_value_cn_name + ')',
					attribute_value_sku: list[i].sku
				};
		}
                if(data.attribute_id == '2' && list[i].attribute_value_id == '201512802'){
                    checkedAttribute = ' selected="selected"';
                }
		if (list[i].selected == 1) {
			checkedAttribute = ' checked="checked"';
			collectSelected(data.attribute_id, list[i].attribute_value_id, true);
		}
		html += '<option children="' + list[i].attribute_children + '" value="' + list[i].attribute_value_id + '"' + checkedAttribute + '>' + list[i].attribute_value_en_name + '(' + list[i].attribute_value_cn_name + ')' + '</option>' + "\n";		
		//html += '<label for="value_id_' + list[i].attribute_value_id + '">' + list[i].attribute_value_en_name + '(' + list[i].attribute_value_cn_name + ')' + '</label>' + "\n";
	}
	html += '</select>' + "\n";
	return html;
}
//生成text input html
function generateTextInputHtml(data, options, type) {
	var html = '<input children="0" name="common_attributes[' + data.attribute_id + ']" type="text" value="" size="32" />'; 
/* 	for (var j in options) {
		html += ' ' + j + '="' + options[j] + '"';
	}
	html += '>' + "\n"; */
	return html;
}
//查找子属性
function findSubAttributes(obj, id, hasChildren) {
	if (typeof(hasChildren) == 'undefined')
		hasChildren = $(obj).find('option:selected').attr('children');
	if (typeof(hasChildren) == 'undefined')
		hasChildren = $(obj).attr('children');
	var valueID = $(obj).val();
	$('.sub_attr_' + id).remove();
	if (hasChildren != '1' || $(obj).val() == '')
		return false;
	var url = 'aliexpress/aliexpresscategory/findsubattributes/attribute_id/' + id + '/value_id/' + valueID;
	$.get(url, function(data){
		var html = '';
		if (data.length > 1) {
			for (var i in data) {
				var options = {};
				options.onchange = 'findSubAttributes(this,' + data[i].attribute_id + ')';
				options.name = 'common_attributes[\'' + data[i].attribute_id + '\'][]';
				html += '<tr class="sub_attr_' + id + '">' + "\n" + '<td class="leftColumn">' + "\n";
				html += '<label>' + generateFlagHtml(data[i]) + data[i].attribute_name_english + '(' + data[i].attribute_name_Chinese + ')' + '：</label>' + "\n";
				html += '</td>' + "\n";
				html += '<td class="rightColumn">' + "\n" + generateHtml(data[i], options) + '</td>' + "\n";
				html += '</tr>' + "\n";
			}
			if (html != '')
				$(obj).parent().parent().after(html);
		} else {
			html = '&nbsp;&nbsp;<input class="sub_attr_' + id + '" type="text" name="common_attributes_custom_value[' + id + '][' + valueID + ']" value="" size="32" />';
			$(obj).after(html);
		}
		return true;
	}, 'json')
}

//保存刊登数据
function saveInfo(){
	console.log($('form#product_add').serialize());
	//return false;
	if (!canSubmitStatus){
		alert(notSubmitReason);
		return false;
	}
	var tabid = $('ul.navTab-tab li.selected').attr('tabid');
	$.ajax({
			type: 'post',
			url: '/aliexpress/aliexpressproductadd/ajax',
			data:$('form#product_add').serialize(),
			success:function(result){
				if(result.status == 'failure'){
					alertMsg.error(result.msg);
				} else if (result.status == 'success'){
					alertMsg.correct('产品修改成功！');
					
					$('form#product_add a.display_list').click();
					navTab.closeTab(tabid);
					navTab.closeTab('_blank');
					navTab.openTab(result.navTabId, '/aliexpress/aliexpressproductadd/list/sku/' + result.sku, {title: 'Aliexpress待刊登列表'});
					
				}
			},
			dataType:'json'
	});
}
//自动填写其他账号对应字段
function autoFill(obj) {
	// var text = $(obj).val();
	// $('input[group=' + $(obj).attr('group') + ']').each(function(){
	// 	$(this).val(text);
	// });
}

//计算利润信息
function loadPriceInfo(self,sku = ''){
	var categoryID = $('input[name=category_id]').val();
	if(sku == ''){
		var sku = $('input[name=publish_sku]').val();
		var accountID = $(self).attr('account_id');
	}else{
		var accountID = $('input[name=account_id\\[\\]]').val();
	}
	var price = $(self).val();
	if (price == false)
		return;
	var data = 'category_id=' + categoryID + '&publish_sku=' + sku + '&' + 'account_id=' + accountID + '&' + 'price=' + price;	
	$.ajax({
		type:'post',
		url:'aliexpress/aliexpressproductadd/getpriceinfo',
		data:data,
		success:function(result){
			$(self).next('.profitDetail').html(
					'&nbsp;&nbsp;<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+result.profit
					+',<?php echo Yii::t('common', 'Profit Rate');?>:'+result.profitRate+'.</font>'
					+'<a href="javascript:;" onClick="alertMsg.confirm(\''+result.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
		},
		dataType:'json'
	});
}

//获取产品价格信息
function getProductPrice() {
	if (_publishType != 1)
		 return false;
	var categoryID = $('input[name=category_id]').val();
	var sku = $('input[name=publish_sku]').val();
	var accounts = $('input[name=account_id\\[\\]]').serialize();
	var data = 'category_id=' + categoryID + '&publish_sku=' + sku + '&' + accounts;
	$.ajax({
		type:'post',
		url:'aliexpress/aliexpressproductadd/getproductprice',
		data:data,
		success:function(result){
			//加载卖价
			if (typeof(result) == 'object') {
				$.each(result,function(i,item){
					i = parseInt(i);
					$('input[name=product_price\\[' + i + '\\]]').val(item.salePrice);
					$('input[name=product_price\\[' + i + '\\]]').removeAttr('disabled');
					$('input[name=product_price\\[' + i + '\\]]').next('.profitDetail').html('&nbsp;&nbsp;<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+item.profit+',<?php echo Yii::t('common', 'Profit Rate');?>:'+item.profitRate+'.</font>'
						+'<a href="javascript:;" onClick="alertMsg.confirm(\''+item.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
				});
			} else {
				alertMsg.error('<?php echo Yii::t('aliexpress_product', 'Get Product Price Error');?>');
			}
		},
		dataType:'json'
	});	
}

//获取多属性产品价格信息
//By AjunLongLive!
function getVariationProductPrice(row) {
	var skuType = '<?php echo $publishParams['publish_type']['id'];?>';
	var mainSku = '<?php echo $publishParams['sku']; ?>';
	var categoryID = $('input[name=category_id]').val();
	var sku = $.trim($('input[name=variation_skus\\[' + row + '\\]]').val());
	//var mainSplitSkuArr = sku.split('.');
	//var mainSplitMainSkuArr = mainSku.split('.');
	var priceElement = $('input[name=variation_price\\[' + row + '\\]]');
	console.log(sku);
	if (sku == '')
		return false;
	/*可以添加其他单品的主sku和子sku，暂时去掉
	if (mainSplitSkuArr[0] != mainSplitMainSkuArr[0]){
		$('input[name=variation_skus\\[' + row + '\\]]').val('');
		alertMsg.error('请添加正确的sku');
		priceElement.val('');
		priceElement.removeAttr('disabled');
		priceElement.next('.profitDetail').html('');		
		return false;
	}
	*/
	if (sku == mainSku && skuType !== '1'){
		$('input[name=variation_skus\\[' + row + '\\]]').val('');
		alertMsg.error('主sku不能进行添加');	
		priceElement.val('');
		priceElement.removeAttr('disabled');
		priceElement.next('.profitDetail').html('');	
		return false;
	}
	$('input[name=variation_skus\\[' + row + '\\]]').parent().parent().parent().find('input').each(function(){
		var thisAttrName = $(this).attr('name');
		if (thisAttrName != undefined){
			//console.log(thisAttrName.indexOf('variation_skus'));
			if (thisAttrName.indexOf('variation_skus') >= 0){
				if (thisAttrName != 'variation_skus[' + row + ']'){
					if ($(this).val() == sku){
						$('input[name=variation_skus\\[' + row + '\\]]').val('');
						alertMsg.error('sku ' + sku + ' 已存在，不能添加一样的sku');
						priceElement.val('');
						priceElement.removeAttr('disabled');
						priceElement.next('.profitDetail').html('');		
						return false;
					}
				}
			}			
		}					
	});
	//后台判断sku的状态
	$.ajax({
		type:'post',
		url:'aliexpress/aliexpressproductadd/check_sku_status',
		data:{skuNo:sku},
		dataType:'json',
		success:function(result){
			console.log(result);
			if (result != null && result.message.length > 0){
				alertMsg.error(result.message);
				$('input[name=variation_skus\\[' + row + '\\]]').val('');
				priceElement.val('');
				priceElement.removeAttr('disabled');
				priceElement.next('.profitDetail').html('');
				return false;
			}
		},
	});	
	var account_id = 0;
	var data = 'category_id=' + categoryID + '&publish_sku=' + sku + '&' + 'account_id[]=' + account_id;
	
	$.ajax({
		type:'post',
		url:'aliexpress/aliexpressproductadd/getproductprice',
		data:data,
		dataType:'json',
		success:function(result){
			//加载卖价
			if (typeof(result) == 'object') {
				$.each(result,function(i,item){
					priceElement.val(item.salePrice);
					priceElement.removeAttr('disabled');
					priceElement.next('.profitDetail').html('&nbsp;&nbsp;<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+item.profit+',<?php echo Yii::t('common', 'Profit Rate');?>:'+item.profitRate+'.</font>'
						+'<a href="javascript:;" onClick="alertMsg.confirm(\''+item.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
				});
			} else {
				alertMsg.error('<?php echo Yii::t('aliexpress_product', 'Get Product Price Error');?>');
			}
		},
	});	
}

//搜集选中的SKU属性值
function collectSelected(attributeID, value, checked) {
	checked = checked || false;
	var attributeID = attributeID;
	var valueID;
	if (typeof(value) == 'object') {
		valueID = $.trim($(value).val());
		checked = $(value).attr('checked');
	} else {
		valueID = value;
	}
	if (typeof(_selectedAttributes[attributeID]) == 'undefined'){
		_selectedAttributes[attributeID] = new Array();
	}
	if (value.nodeName == 'SELECT') {
		_selectedAttributes[attributeID] = new Array();
		checked = true;
	}
	if (checked && valueID && $.inArray(valueID, _selectedAttributes[attributeID]) === -1) {
		_selectedAttributes[attributeID].push(valueID);
	} else {
		_selectedAttributes[attributeID].splice($.inArray(valueID, _selectedAttributes[attributeID]), 1);
		//addCustomAttribute(attributeID, valueID);
	}
	//生成多属性产品
	generateVariationProduct();
}

function compare(value1, value2) {
	   if (value1 < value2) {
	       return 1;
	   } else if (value1 > value2) {
	       return -1;
	   } else {
	       return 0;
	   }
}
//生成多属性产品html
function generateVariationProduct() {
	var columns = new Array();	//属性列
	var rows = new Array();	//多属性产品行
	var variationPublishFlag = false;	//单品刊登到有SKU属性的分类，是否显示多属性产品填写列标记
	for (var i in _selectedAttributes) {
		if(_selectedAttributes[i].length <= 0) continue;
		columns.push(i);
		if (rows.length <= 0) {
			var flag = 0;
			for (var j in _selectedAttributes[i]) {
				rows[j] = new Array();
				rows[j][i] = _selectedAttributes[i][j];
				if (!variationPublishFlag)// && flag >= 1)
					variationPublishFlag = true;
				flag++;
			}
		} else {
			var tmpRows = new Array();
			var key = 0;
			var flag = 0;
			for (var j in rows) {
				for (var k in _selectedAttributes[i]) {
					tmpRows[key] = new Array();
					if (!variationPublishFlag) //&& flag >= 1)
						variationPublishFlag = true;
					flag++;
					for (var l in rows[j]) {
						tmpRows[key][l] = rows[j][l];
					}
					tmpRows[key][i] = _selectedAttributes[i][k];
					key++;
				}
			}
			rows = tmpRows;
		}
	}

	//var html = '<table><tbody>' + "\n";
	//html += '<tr>' + "\n";
	//html += '<td>统一调价金额：<input type="" name="" /></td>' + "\n";
	//html += '<td>统一调价百分比：<input type="" name="" /></td>' + "\n";
	//html += '</tr>' + "\n";
	//html += '</tbody></table>' + "\n";
	var html = '<table class="variationProductTable"><tbody>' + "\n";
	//如果是多属性刊登或者一口价产品刊登到有SKU属性的分类且同一属性选择了两个或者两个以上属性
	if (_publishType == 2 || variationPublishFlag) {
		//多属性产品列表表头
		html += '<tr>' + "\n";
		var isCustomName = false;
		for (var i in columns) {
			html += '<th><span>' + _skuAttributes[columns[i]].attribute_name + '</span></th>' + "\n";
			if (_skuAttributes[columns[i]].attribute_customized_name == 1)
				isCustomName = true;
		}
		if (isCustomName)
			html +='<th><span><?php echo Yii::t('aliexpress', 'Custom Name');?></span></th>' + "\n"; 
		html += '<th><span><?php echo Yii::t('aliexpress_product', 'SKU');?></span></th>' + "\n";
		html += '<th><span><?php echo Yii::t('aliexpress_product', 'Price');?></span>&nbsp;&nbsp;<input type="text" id="batchSetSkuPrice" style="width:80px;" />&nbsp;&nbsp;<a href="javascript:void(0);" onclick="batchSetSkuPrice(this)">设置</a></th>' + "\n";
		html += '</tr>';
		
		for (var i in rows) {
			//读取当前行已经填写的值
			var rowClass = '';
			var columnHtml = '';
			var sku, price, priceDetials, customName;
			var key = 0;
			var kets = new Array();
			for (var j in rows[i]) {
				key += parseInt(rows[i][j]);
				kets.push(j);
				kets.push(rows[i][j]);
				sku = _skuAttributes[j].value_list[rows[i][j]].attribute_value_sku;
				rowClass += '_' + rows[i][j];
				columnHtml += '<td><span>' + _skuAttributes[j].value_list[rows[i][j]].attribute_value_name + '</span>' + "\n" + '<input type="hidden" name="variation_attributes[' + i + '][' + j + ']" value="' + rows[i][j] + '" /></td>' + "\n";
			}
			
			kets = kets.join("-");
			for (var k in _variationSkus) {
				if (key == k) {
					//sku = _variationSkus[k].sku;
					
					price = _variationSkus[k].price;

					customName = _variationSkus[k].customName;
				}
			}
			try{
				sku = _selectedSkuMap[kets];
				if(sku == undefined){
					for (var k in _variationSkus) {
						if (key == k) {
							sku = _variationSkus[k].sku;
						}
					}
				}
			}catch(e){
				sku = '';
			}
			sku = sku || $('tr.variation_row' + rowClass).find('input[name^=variation_skus]').val();
			if (typeof(sku) == 'undefined')
				sku = '';
			price = price || $('tr.variation_row' + rowClass).find('input[name^=variation_price]').val();
			if (typeof(price) == 'undefined')
				price = '';
			//price = $('tr.variation_row' + rowClass).find('input[name^=variation_price]').val();
			priceDetials = $('tr.variation_row' + rowClass).find('input[name^=variation_price]').next('.profitDetail').html();
			customName = customName || $('tr.variation_row' + rowClass).find('input[name^=variation_custom_name]').val();
			if (typeof(price) == 'undefined')
				price = '';
			if (typeof(priceDetials) == 'undefined')
				priceDetials = '';
			if (typeof(customName) == 'undefined')
				customName = '';
			
			html += '<tr class="variation_row' + rowClass + '">' + "\n" + columnHtml;
			if (isCustomName)
				html += '<td><input type="text" name="variation_custom_name[' + i + ']" value="' + customName + '" size="18" /></td>' + "\n";
			html += '<td><input type="text" onchange="getVariationProductPrice(' + i + ')" name="variation_skus[' + i + ']" value="' + sku + '" size="22" /></td>' + "\n";
			html += '<td><input type="text" class="sku_variation_price"  onblur="loadPriceInfo(this,' + sku + ')" name="variation_price[' + i + ']" value="' + price + '" size="8" />';
			//html += '<td><input type="text" name="variation_skus[' + i + ']" value="' + sku + '" size="22" /></td>' + "\n";
			//html += '<td><input type="text" class="sku_variation_price" name="variation_price[' + i + ']" value="' + price + '" size="8" />';
			html += '<span class="profitDetail"><?php //添加卖价详情说明?>' + priceDetials + '</span></td>' + "\n";
			html += '</tr>' + "\n";
		}
	}
	html += '</tbody></table>';
	$('table.variationProductTable').remove();
	$('#skuAttributes').append(html);
	//$('input[name^=variation_skus]').change();
}
//生成可自定义属性的html
function addCustomAttribute(attrID, valueID) {
	var html = '<table class="customAttributeTable"><tbody>';
	html = '<tr>' + "\n";
	html += '<th>' + _skuAttributes[attrID].attribute_name + '</th>' + "\n";
	html += '<th><?php echo Yii::t('aliexpress_product', 'Custom Attribute Name');?></th>' + "\n";
	html += '<th><?php echo Yii::t('aliexpress_product', 'Custom Attribute Image');?></th>' + "\n";
	html += '</tr>' + "\n";
	html += '<tr>' + "\n";
	html += 'td>' + _skuAttributes[attrID].value_list[valueID].attribute_value_name + '</td>' + "\n";
	html += '<td><input type="text" name="" value="" size="16" /></td>' + "\n";
	html += '<td><input type="file" name="" value="" /></td>' + "\n";
	html += '</tr>' + "\n";
	html += "\n" + '</tbody></table>';
	$('#customAttributesBox').append();
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
//添加自定义属性
var _customAttributeRowCount = 0;
function addCustomAttributes() {
	if (_customAttributeRowCount >= 10) return false;
	var html = '<tr>' + "\n";
	html += '<td><input size="32" type="text" name="custom_attribute_name[]" value="<?php echo Yii::t('aliexpress', 'Attribute Name');?>" onblur="if(this.value == \'\') this.value=\'<?php echo Yii::t('aliexpress', 'Attribute Name');?>\';" onfocus="if(this.value==\'<?php echo Yii::t('aliexpress', 'Attribute Name');?>\') this.value=\'\'" /></td>' + "\n";
	html += '<td><input size="32" type="text" name="custom_attribute_value[]" value="<?php echo Yii::t('aliexpress', 'Attribute Value');?>" onblur="if(this.value == \'\') this.value=\'<?php echo Yii::t('aliexpress', 'Attribute Value');?>\';" onfocus="if(this.value==\'<?php echo Yii::t('aliexpress', 'Attribute Value');?>\') this.value=\'\'" /></td>' + "\n";
	html += '<td><a href="javascript:void(0)" onclick="deleteCustomAttributeRow(this)"><?php echo Yii::t('aliexpress', 'Delete Row');?></a></td>' + "\n";
	html += '</tr>' + "\n";
	$('.customAttributes table').append(html);
	_customAttributeRowCount++;
	
}
function deleteCustomAttributeRow(obj) {
	$(obj).parents('tr:eq(0)').remove();
	_customAttributeRowCount--;
}

function batchSetSkuPrice(obj){
	var batchprice = parseFloat($("#batchSetSkuPrice").val());
	if(batchprice == NaN ){
		batchprice = 0;
	}

	$("input.sku_variation_price").each(function(i, n){
		$(n).val(batchprice);
	});
}
$(".ztimgs .extra_checked").live('click', function(event){
	var checked = !!$(this).attr("checked");
	if(checked){
		if($(".ztimgs .extra_checked:checked").length>6){
			alert('主图最大选择6个!');
			return false;
		}
	}
	event.stopPropagation();
});
$(".ftimgs .extra_checked").live('click', function(event){
	 event.stopPropagation();
});
$(".extra_checked").mousedown(function(event){
	 event.stopPropagation();
});
</script>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
	    <div class="bg14 pdtb2 dot">
	         <strong>SKU：[<?php echo $publishParams['sku'];?>]</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
	       <div class="row productAddInfo" style="width:90%;float:left;">
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
                'action' => Yii::app()->createUrl('aliexpress/aliexpressproductadd/savedata'), 
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
                                  		CHtml::link($publishParams['sku'], '/products/product/viewskuattribute/sku/'.$publishParams['sku'], 
                    			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
                    			?></li> 
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font><?php echo $publishParams['publish_type']['text'];?></li>
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Mode')?>：</font><?php echo $publishParams['publish_mode']['text'];?></li>
				                </ul>
				                <input type="hidden" name="publish_sku" value="<?php echo $publishParams['sku'];?>" />
				                <input type="hidden" name="publish_type" value="<?php echo $publishParams['publish_type']['id'];?>" />
				                <input type="hidden" name="publish_mode" value="<?php echo $publishParams['publish_mode']['id'];?>" />
				                <?php if ($action == 'update') { ?>
				                <input type="hidden" name="action" value="update" />
				                <input type="hidden" name="id" value="<?php echo $addInfo['productId'];?>" />
				                <?php } else { ?>
				                <input type="hidden" name="action" value="create" />
				                <?php } ?>
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

                                    </div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                    	<?php if (!empty($skuImg) && isset($skuImg['zt'])) { ?>
                                            <?php $count = 0; $selected = 0; ?>
                                    	<?php foreach($skuImg['zt'] as $k=>$image):?>
                                    	<?php if(isset($selectedImages['zt']) && $action == 'update' && in_array($k, $selectedImages['zt'])):?>
                                        <?php $selected++;?>
                                        <?php endif;?>
                                        <div style="position:relative;" class="aliexpress_image">
                                            <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" key="<?php echo $k;?>" class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[1][]"
                                            <?php 
                                                $ztValue = isset($selectedImages['zt']) ? $selectedImages['zt'] : array();
                                            ?>
    				                        <?php if(($count < 6 && $action != 'update') || ( $selected < 6 && $action == 'update' && in_array($k, $ztValue))):?>checked<?php endif;?>
                                            />
                                        </div>
                                        <?php $count++;?>
                                        <?php endforeach;?>
                                        <?php } ?>
                                    </div> 
                                        
                                    <div style="clear:both;"></div>
                                </div>
                               
                                
                                <div class="page unitBox ftimgs">
                                    <div><strong><?php echo Yii::t('aliexpress_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php if (!empty($skuImg)  && isset($skuImg['ft'])) { ?>
                                        <?php $count = 0;?>
                                        <?php foreach($skuImg['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" key="<?php echo $k;?>" class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[2][]"
                                                <?php 
                                                    $ftValue = isset($selectedImages['ft']) ? $selectedImages['ft'] : array();
                                                ?>  
                                                <?php if(($count < 15 && $action != 'update') || ( $selected < 15 && $action == 'update' && in_array($k, $ftValue))):?>checked<?php endif;?>
                                               
                                            />
                                        </div>
                                        <?php $count++;?>
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
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Category');?></td>
				            <td>
				            	<div class="categoryBox">
				            		<div class="tabHeader">
				            			<ul class="tabHeaderList">
				            			<?php if (!empty($historyCategoryList)) { ?>
				            				<li class="tab1 on"><a href="#"><?php echo Yii::t('aliexpress_product', 'History Category');?></a></li>
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
				            				<?php 
				            				    $requestUsers = Yii::app()->request->getParam('accounts');
				            				    if (count($requestUsers) == 1 || isset($accountsUpdate)){
				            				        $userBindCategory = AliexpressAccountBindCategory::model()->getUserBindCategoryByUserID(isset($accountsUpdate) ? $accountsUpdate[0] : $requestUsers[0]);
				            				        $firstCategoryID = explode(',',$userBindCategory['first_category']);
				            				        echo "
                                                            <script>
                                                                var secondCategory = '{$userBindCategory['second_category']}';
                                                                var thirdCategory = '{$userBindCategory['third_category']}';
                                                            </script>
                                                         ";
				            				    }
				            				?>
				            				<?php 
				            				    foreach($chooseCategoryList as $cateID => $cateName) { 
				            				        if (isset($firstCategoryID) && !in_array($cateID, $firstCategoryID)){
				            				            continue;
				            				        }
				            				?>
				            					<option value="<?php echo $cateID;?>"><?php echo $cateName;?></option>
				            				<?php } ?>
				            				</select>
				            			</div>
				            		</div>
				            		<div class="tabFooter">
				            			<input size="125" class="textInput" type="text" name="category_name" value="<?php echo end($defaultHistoryCategory);?>" />
				            			<input type="hidden" value="<?php echo key($defaultHistoryCategory);?>" name="category_id" />
				            			<a href="#" onclick="findCategoryAttributes()" class="btn" id="categoryConfirmBtn"><?php echo Yii::t('aliexpress_product', 'Confirm Choose Category');?></a>
				            			<a href="#" onclick="syncCategoryAttributes()" class="btn"><?php echo Yii::t('aliexpress_product', '同步该类目属性');?></a>
				            			<a href="#" onclick="syncCategoryAttributesBrand()" class="btn"><?php echo Yii::t('aliexpress_product', '同步该类目品牌');?></a>
				            		</div>
				            	</div>
				            </td>
				        </tr>
				        <!-- 类别显示END -->
				        
                        <!-- sku属性显示START -->
                        <tr id="skuAttrRow"<?php echo ($action != 'update') ? ' style="display:none"' : '';?>>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Sale Attribute');?></td>
				            <td>
				                <div id="skuAttributes">
				                <?php if ($action == 'update') { ?>
				                <table id="skuAttributeTable" class="attributesTable">
									<tbody>
				                		<?php foreach ($skuAttributes as $skuAttribute) { ?>
										<tr id="attr_<?php echo $skuAttribute['attribute_id'];?>">
											<td class="leftColumn">
												<label><?php echo $skuAttribute['attribute_name_english'] . '(' . $skuAttribute['attribute_name_Chinese'] . ')';?></label>
											</td>
											<td class="rightColumn">
												<?php //foreach ($skuAttribute as $valueList) { ?>
												<?php echo $this->renderPartial('application.modules.aliexpress.components.views.generate_html', array('data' => $skuAttribute, 'options' => array(), 'type' => 'sku', 'sku'=>$publishParams['sku']));?>
												<?php //} ?>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
				                <?php } ?>
				                </div>
				                <div id="productVariations">
				                </div>
				            </td>
				        </tr>
                        <!-- sku属性显示END -->
                        
                        <!-- 分类属性显示START -->
                        <tr id="commomAttrRow"<?php echo ($action != 'update') ? ' style="display:none"' : '';?>>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Category Attribute');?></td>
				            <td>
				                <div id="commonAttributes">
				                <?php if ($action == 'update') { ?>
				                <table id="commonAttributeTable" class="attributesTable">
									<tbody style="position:relative;"><br /><br />
				                		<?php //print_r($commonAttributes); ?>
				                		<?php foreach ($commonAttributes as $commonAttribute) { ?>
				                		<?php 
				                		      $searchTd = '';
				                		      $searchHtml = '';
				                		      $searchHtmlSelectStyle = '';
				                		      if ($commonAttribute['attribute_name_Chinese'] == '品牌'){
				                		          $searchTd = 'ajunBrandSelectTd';
				                		          $searchHtml  = '<br /><br />';
				                		          $searchHtml .= '<div style=" background:none; position:absolute;  margin-left:0px; margin-top:-28px; z-index:99; " id="ajunBrandSelectDiv">';				                		          
				                		          $searchHtml .= '<input type="text" style="width:99px; " id="ajunBrandSelectCategory" />';
				                		          $searchHtml .= '&nbsp;&nbsp;';
				                		          $searchHtml .= '<input type="button" value=" 搜索品牌 " id="ajunBrandSelectSubmit" />';
				                		          $searchHtml .= '&nbsp;&nbsp;';
				                		          $searchHtml .= '<input type="button" value=" 清空 " id="ajunBrandSelectClear" />';
				                		          $searchHtml .= '</div>';
				                		          //$options.id  = 'ajunBrandSelect';
				                		          //searchHtmlButton = '&nbsp;&nbsp;&nbsp;<input type="button" value=" 搜索品牌 " id="ajunBrandSelectButton" />';
				                		          $searchHtmlSelectStyle = ' style="z-index:66;" ';
				                		      }
				                		?>
										<tr id="attr_<?php echo $commonAttribute['attribute_id'];?>">
											<td class="leftColumn">
												<?php echo ($commonAttribute['attribute_required'] == 1) ? ('<span class="attributeFlag required">*</span>' . "\n") : (($commonAttribute['attribute_key_attribute'] == 1) ? '<span class="attributeFlag required">！</span>' . "\n" : '');?>
												<label><?php echo $commonAttribute['attribute_name_english'] . '(' . $commonAttribute['attribute_name_Chinese'] . ')';?></label>
											</td>
											<td class="rightColumn <?php echo $searchTd; ?>" <?php echo $searchHtmlSelectStyle; ?>>
											    <?php echo $searchHtml; ?>
												<?php echo $this->renderPartial('application.modules.aliexpress.components.views.generate_html', array('data' => $commonAttribute, 'type' => 'common', 'sku'=>$publishParams['sku']));?>
												<?php //} ?>
												<div id="customAttributesBox"></div>
											</td>
										</tr>
										<?php //print_r($commonAttribute); ?>
										<?php } ?>
									</tbody>
								</table>
				                <?php } ?>				                
				                </div>
				                <div class="customAttributes">
				                	<table cellspacing="0" cellpadding="0">
				                		<tbody>
				                			<tr>
				                				<td colspan="3"><a href="javascript:void(0)" onclick="addCustomAttributes()"><?php echo Yii::t('aliexpress', 'Add Custom Attributes');?></a></td>
				                			</tr>
				     				    <?php if (isset($customAttributes) && !empty($customAttributes)) { ?>
				     				    	<?php foreach ($customAttributes as $customAttribute) { ?>
				     				    	<tr>
				     				    		<td><input size="32" type="text" name="custom_attribute_name[]" value="<?php echo $customAttribute['attribute_name'];?>" onblur="if(this.value == '') this.value='<?php echo Yii::t('aliexpress', 'Attribute Name');?>';" onfocus="if(this.value=='<?php echo Yii::t('aliexpress', 'Attribute Name');?>') this.value=''" /></td>
												<td><input size="32" type="text" name="custom_attribute_value[]" value="<?php echo $customAttribute['value_name'];?>" onblur="if(this.value == '') this.value='<?php echo Yii::t('aliexpress', 'Attribute Value');?>';" onfocus="if(this.value=='<?php echo Yii::t('aliexpress', 'Attribute Value');?>') this.value=''" /></td>
												<td><a href="javascript:void(0)" onclick="deleteCustomAttributeRow(this)"><?php echo Yii::t('aliexpress', 'Delete Row');?></a></td>			     				    	
				     				    	</tr>
				     				    	<?php } ?>
				                		<?php } ?>           			
				                		</tbody>
				                	</table>
				                </div>
				            </td>
				        </tr>
                        <!-- 分类属性显示END -->  
                                              				        
				        <!-- 基本信息显示START -->
                        <tr>
				            <td rowspan="2" width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Base Info');?></td>
				            <td>
				                <div class="tabs"> 
	                                <div class="tabsHeader"> 
	 		                            <div class="tabsHeaderContent"> 
                            	 			<ul> 
                            	 			    <?php $k = 0;foreach($accountList as $accountID => $account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['account_name'];?>&nbsp;&nbsp;</span></a>
                            	 					<input type="hidden" name="account_id[]" value="<?php echo $accountID?>" />
                            	 				</li>
                            	 				<?php $k++;endforeach;?>
                            	 			</ul> 
                            	 		</div> 
                            	 	</div>
                            	 	<div class="tabsContent"> 
                            	 	    <?php foreach($accountList as $accountID=>$account):?>
                            	 	    <div class="pageFormContent" style="border:1px solid #B8D0D6">
                            	 	    <table class="baseinfoTable" width="98%">
                            	 			<tbody>
                             			    <tr>
                             			        <td>
                             			    		<span><?php echo Yii::t('aliexpress_product', 'Product Title');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<input type="text" group="subject" onchange="autoFill(this)" name="subject[<?php echo $accountID?>]" value="<?php echo isset($account['product_title']) ? $account['product_title'] : '';?>" onblur = "checkStrLength(this,128)" maxlength="128" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>
                   	           				<?php if ($publishParams['publish_type']['id'] == AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE) { ?>
                            	            <tr id="fixedPriceRow">
                            	            	<td><span><?php echo Yii::t('aliexpress_product', 'Product Fixed Price');?>：</span></td>
                            	            	<?php if ($action == 'update') { ?>
                            	            	<!-- onBlur="loadPriceInfo(this)" --> 
                            	            		<td><input account_id="<?php echo $accountID;?>" type="text" name="product_price[<?php echo $accountID?>]" value="<?php echo $account['product_price'];?>" size="12" /><span class="profitDetail"><?php //添加卖价详情说明?></span></td>
                            	            	<?php } else {?>
                            	            	<!-- onBlur="loadPriceInfo(this)" -->
													<td><input account_id="<?php echo $accountID;?>"  placeholder="<?php echo Yii::t('lazada','Need Specific Category');?>" type="text" name="product_price[<?php echo $accountID?>]" value="" size="12" /><span class="profitDetail"><?php //添加卖价详情说明?></span></td>
                            	            	<?php } ?>
                            	            </tr>
                            	            <?php } ?>
                            	            <!--  
                            	            <tr>
                            	            	<td><span>产品折扣</span></td>
                            	            	<td>
                            	            		<input size="3" type="text" name="discount[<?php echo $accountID?>]" value="<?php echo isset($addInfo['discount']) ? $addInfo['discount'] : 0;?>" /><span style="line-height:32px;padding-left:10px;">请输入1-100之间的数字，如8折请填写80</span>
                            	            	</td>
                            	            </tr>
                            	            -->
                            	            
                            	            <tr>
                            	            	<td><span><?php echo Yii::t('aliexpress_product', 'Product Group');?></span></td>
                            	            	<td>
                            	            		<select name="group_id[<?php echo $accountID?>]">
                            	            			<option value=""><?php echo Yii::t('system', 'Please Select')?></option>
                            	            			<?php echo isset($account['group_list']) ? $account['group_list'] : '';?>
                            	            		</select>
                            	            	</td>
                            	            </tr>
                            	            <tr>
                            	            	<td><span><?php echo Yii::t('aliexpress', 'Freight Template');?></span></td>
                            	            	<td>
                            	            		<select name="freight_template_id[<?php echo $accountID?>]" id="freight_template_id" style="float:left;">
                            	            			<option value=""><?php echo Yii::t('system', 'Please Select')?></option>
                            	            			<?php echo $account['freight_list'];?>
                            	            		</select><div id="updateFrieght" class="categoryBox"><a href="javascript:void(0)" class="btn">更新运费模板</a></div>
                            	            	</td>
                            	            </tr>
                            	            <tr>
                            	            	<td><span><?php echo Yii::t('aliexpress', 'Services Template');?></span></td>
                            	            	<td>
                            	            		<select name="service_template_id[<?php echo $accountID?>]" id="service_template_id" style="float:left;">
                            	            			<option value=""><?php echo Yii::t('system', 'Please Select')?></option>
                            	            			<?php if (isset($account['service_list'])) echo $account['service_list'];  ?>
                            	            		</select><div id="updateService" class="categoryBox"><a href="javascript:void(0)" class="btn">更新服务模板</a></div>
                            	            	</td>
                            	            </tr>
					                        <!-- 产品描述START -->
					                        <tr>
					                        	<td><?php echo Yii::t('aliexpress', 'Product Description');?></td>
					                        	<td>
					                        		<textarea rows="42" cols="22" name="detail[<?php echo $accountID?>]" class="productDescription"><?php echo $account['description'];?></textarea>
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
				            	<div>
				            		<table class="baseinfoTable">
				            			<tbody>
				            				<tr>
				            					<td class="leftColumn"><label><?php echo Yii::t('aliexpress', 'Product Unit');?></label></td>
				            					<td class="rightColumn">
				            						<select name="product_unit">
				            							<option value=""><?php echo Yii::t('system', 'Please Select')?></option>
				            							<?php echo $productUnitList;?>
				            						</select>
				            					</td>
				            				</tr>
				            				<tr>
				            					<td class="leftColumn"><label><?php echo Yii::t('aliexpress', 'Sale Package');?></label></td>
				            					<td class="rightColumn">
				            						<input size="6" type="text" name="lot_num" value="<?php echo isset($addInfo['lotNum']) ? $addInfo['lotNum'] : '';?>" />&nbsp;&nbsp;
				            						<label><?php echo Yii::t('aliexpress', 'Per Bag');?></label>
				            					</td>
				            				</tr>
				            				<tr>
				            					<td class="leftColumn"><label><?php echo Yii::t('aliexpress', 'Product Package Weight');?></label></td>
				            					<td class="rightColumn"><input size="6" type="text" name="gross_weight" value="<?php echo $action == 'update' ? $addInfo['grossWeight'] : $skuInfo['grossWeight'];?>" />&nbsp;&nbsp;<?php echo Yii::t('aliexpress', 'Unit Kilogram');?></td>
				            				</tr>
				            				<tr>
				            					<td class="leftColumn"><label><?php echo Yii::t('aliexpress', 'Product Package Size');?></label></td>
				            					<td class="rightColumn">
				            						<ul class="productSize">
				            							<li><input type="text" size="6" name="package_length" value="<?php echo $action == 'update' ? $addInfo['packageLength'] : $skuInfo['packageLength'];?>" /></li>
				            							<li><input type="text" size="6" name="package_width" value="<?php echo $action == 'update' ? $addInfo['packageWidth'] : $skuInfo['packageWidth'];?>" /></li>
				            							<li><input type="text" size="6" name="package_height" value="<?php echo $action == 'update' ? $addInfo['packageHeight'] : $skuInfo['packageHeight'];?>" />&nbsp;&nbsp;<?php echo Yii::t('aliexpress', 'Unit Centimeter');?></li>
				            						</ul>
				            					</td>
				            				</tr>				            							            				
				            			</tbody>
				            		</table>
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
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('lazada', 'Save Into List');?></a>&nbsp;
                                </div>
                            </div>
                        </li>
                        <!-- <li>
                            <div class="buttonActive" style="margin-left:20px;">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)">
                                        <?php //echo Yii::t('lazada', 'Upload Now')?>
                                    </a>
                                </div>
                            </div>
                        </li> -->
                        <a href="<?php echo Yii::app()->createUrl('aliexpress/aliexpressproductadd/list/sku/'.$publishParams['sku'].'/status/'.AliexpressProductAdd::UPLOAD_STATUS_DEFAULT);?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('common','Product Add List');?></a>
                    </ul>
                </div>
                <textarea rows="0" cols="0" name="onlineData" style="display:none;"><?php echo base64_encode(json_encode($addInfo)); ?></textarea>
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
    				            <?php if (isset($recycleImages) && !empty($recycleImages)) { 
    				            		foreach ($recycleImages as $type => $recycleImage) {
											foreach ($recycleImage as $k => $image) {
    				            ?>
    				            	<div style="position:relative;" class="aliexpress_image2">
                                    	<img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
                                    	<input type="hidden" value="<?php echo $image;?>" name="skuImage[<?php echo $type;?>][]" />
                                    </div>
    				            <?php 
											}
										}
								} ?>
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
var _keywords = $('input[name=search_keywords]').val();
$(function(){
	$('ul.tabHeaderList li').click(function(){
		changeTab(this);
	});
	<?php if ($action != 'update') { ?>
	$('select[name=category_list_history]').change();
	<?php } ?>

	//更新运费模板
	$('#updateFrieght a').click(function(){
		var getAccountId = "<?php echo $accountID?>";
        var url ='/aliexpress/aliexpressfreighttemplate/getfreighttemplatebyaccountid';
        var param = {'account_id':getAccountId};

        $.post(url, param, function(data){
            if(data != null && data.statusCode == 200){
            	$('#freight_template_id').empty();
			    $('#freight_template_id').append(data.data);
                alertMsg.correct(data.message);
            }else{
                alertMsg.error(data.message);
            }
        }, 'json');
	});

	//更新服务模板
	$('#updateService a').click(function(){
		var getAccountId = "<?php echo $accountID?>";
        var url ='/aliexpress/aliexpresspromisetemplate/getpromisetemplatebyaccountid';
        var param = {'account_id':getAccountId};

        $.post(url, param, function(data){
            if(data != null && data.statusCode == 200){
            	$('#service_template_id').empty();
			    $('#service_template_id').append(data.data);
                alertMsg.correct(data.message);
            }else{
                alertMsg.error(data.message);
            }
        }, 'json');
	});
	
	$(document).on('click','#ajunBrandSelectSubmit',function(){
		//$('#ajunBrandSelectDiv').hide();
		//$('#ajunBrandSelectButton').show();
		//alertMsg.correct('如果品牌很多，页面可能会卡死一会儿！');
		var ajunBrandSelectValue = $('#ajunBrandSelectCategory').val();
		var ajunBrandSelectValueArr = ajunBrandSelectValue.split(',');
		console.log(ajunBrandSelectValue);
		$('#ajunBrandSelect').find('option').each(function(){
			//console.log($(this).html());
			for (brandValue in ajunBrandSelectValueArr){
				console.log(ajunBrandSelectValueArr[brandValue]);
				var thisHtml  = $(this).html().toLowerCase();
				var eachValue = ajunBrandSelectValueArr[brandValue].toLowerCase();
				if (thisHtml.indexOf(eachValue) >= 0){
					$(this).show();
					break;
				} else {
					$(this).hide();
				}
			}			
		});
		
	});	

	$(document).on('change','#ajunBrandSelect',function(){
		console.log('change' + new Date());		
		//$('#ajunBrandSelectDiv').hide();
		//$('#ajunBrandSelectButton').show();
		
	});	
	
	$(document).on('click','#ajunBrandSelectClear',function(){
		$('#ajunBrandSelectCategory').val('');		
	});	
	
	if (($('#ajunBrandSelect').width() - $('#ajunBrandSelectCategory').width()) > 158 ){
		$('#ajunBrandSelectCategory').css('width',$('#ajunBrandSelect').width() - 158);
	}
	$('#ajunBrandSelectCategory').attr('title','如果品牌很多，页面可能会卡死一会儿！');
	setTimeout(function(){
		$('#ajunBrandSelectCategory').attr('class','');
		$('#ajunBrandSelectCategory').removeClass('textInput');
	},1000)
});
KindEditor.create('textarea.productDescription',{
	allowFileManager: true,
	width: '90%',
	height: '450',
	afterCreate : function() {
    	this.sync();
    },
    afterBlur:function(){
        this.sync();
    },
});
generateVariationProduct();
</script>
<div style="position:absolute; top:18%; left:8%; width:68%; height:58%; background:#f6f6f6; z-index:666666; display:none; padding:28px; font-size:18px; line-height:28px;" 
     id="errorBox" 
     onClick="$(this).html(''); $(this).fadeOut(666);"
><div>