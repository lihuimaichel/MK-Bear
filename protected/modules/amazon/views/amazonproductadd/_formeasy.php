
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

.pageContent .attributesTable input.textInput {
	width:65px;
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
	margin:0;
}
table.attributesTable{
	padding:0;
	margin:0;
	width:100%;
}
table.dataintable_inquire td{
	vertical-align: top;
}
table.attributesTable td, table.baseinfoTable td{
	vertical-align:middle;
	padding:7px 5px;
}
table.attributesTable td.leftColumn {
	text-align:right;
	width:10%;
}
table.attributesTable td.rightColumn {
	text-align:left;
	width:90%;
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

-->
</style>
<script type="text/javascript">
var _selectedAttributes = new Array();		//选择的sku属性
var _skuAttributes = new Array();
var _publishType = <?php echo $publishParams['publish_type']['id'];?>;
var _variationSkus = new Array();
var _selectedSkuMap = new Array();
<?php 
if (isset($variationAttributes) && !empty($variationAttributes)) {
	foreach ($variationAttributes as $skuAttribute) {
		$key = 0;
		foreach ($skuAttribute['attributes'] as $attributes) {
			$key += $attributes['attribute_value_id'];
		}
		echo '_variationSkus[' . $key . '] = new Array();' . "\n";
		echo '_variationSkus[' . $key . '].sku = \'' . $skuAttribute['sku'] . '\'' . "\n";
		echo '_variationSkus[' . $key . '].price = \'' . $skuAttribute['price'] . '\'' . "\n";
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

//清除分类属性
function clearnSelectedCategoryAttributes() {
	$('#commonAttributes').empty();
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
	// if (keywords == '' || keywords == _keywords) {
	if (keywords == '') {
		alertMsg.error('<?php echo Yii::t('amazon_product', 'Please Input Keywords');?>');
		return false;
	}

	$('select[name=category_list_search]').empty();
	var url = 'amazon/amazoncategory/getcategorylistbykeyword/keyword/' + keywords;
	$.get(url, function(data){;
		if (data.statusCode == '200') {
			var categoryList = data.categoryList;
			var options;
			for (var i in categoryList) {
				options += '<option value="' + i + '">' + categoryList[i] + '</option>' + "\n";
			}
			$('select[name=category_list_search]').html(options);
		} else {
			alertMsg.error('没有数据');
			return false;
		}
	}, 'json');
}
//查找子类别
function findSubCategory(obj) {
	clearnSelectedCategory();
	var cateID = $(obj).val();
	var url = 'amazon/amazoncategory/findsubcategory/category_id/' + cateID;
	$.get(url, function(data){
		$(obj).nextAll().remove();
		if (data.statusCode == '200') {
			var level = parseInt(100*Math.random());	//随机整数
			var categoryList = data.category_list;
			var html = '<select size="16" style="min-width:250px;" name="category_list_choose_level_' + level + '" onclick="findSubCategory(this)">' + "\n";
			for (var i in categoryList)
				html += '<option value="' + i + '">' + categoryList[i] + '</option>' + "\n";
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
//清除分类属性文本
function clearnAttributes() {
	_skuAttributes = new Array;
	_selectedAttributes = new Array();
	$('#skuAttrRow').hide();
	//$('#commomAttrRow').hide();
	$('#skuAttributes').empty();
	//$('#commonAttributes').empty();
}

//同步该类目下面的分类属性
function syncCategoryAttributes(){
	var cateID = $('input[name=category_id]').val();
	if (cateID == '0')
		return false;
	var url = 'aliexpress/aliexpresscategory/getcategoryattributes/category_id/' + cateID;
	$.get(url, function(data){
		if (data.statusCode != 200) {
			alertMsg.error(data.message);
			return false;
		}
		findCategoryAttributes();
	}, 'json');
	
}
//查找分类下的属性
function findCategoryAttributes(flag) {
	var category_name = $('input[name=category_name]').val();
	var addID = $('input[name=id]').val();
	var cateID = $('input[name=category_id]').val();
	if (category_name.length == 0) return false;
	
	if (typeof(addID) == 'undefined') addID = 0;
	if (typeof(flag) == 'undefined') addID = 0;
	clearnAttributes();
	clearnSelectedCategoryAttributes();

	if (cateID == '0')
		return false;
	var url = 'amazon/amazoncategory/findcategoryattributes/category_id/' + cateID + '/sku/<?php echo $publishParams['sku'];?>/addid/' + addID;
	$.get(url, function(data){
		if (data.statusCode != '200') {
			alertMsg.error(data.message);
			//获取产品价格
			getProductPrice();
			return false;
		}
		var skuAttributes           = data.sku_attributes;		
		_selectedSkuMap             = data.selected_sku_map;		
		// var selectedCateogry     = data.selected_cateogry;
		var commonAttributes        = data.selected_cateogry;
		var topCategoryList         = data.top_category;
		var item_type               = data.item_type;
		var cate_product_type       = data.product_type;
		// var selectedProductTypeText = data.selectedProductTypeText;	//XSD分类

		//生成sku属性
		var html = '<table id="skuAttributeTable" class="attributesTable"><tbody>' + "\n";
		for (var i in skuAttributes) {
			//将属性保存在变量里面
			_skuAttributes[skuAttributes[i].attribute_id] = {
						attribute_id:skuAttributes[i].attribute_id,
						attribute_spec:skuAttributes[i].attribute_spec,
						attribute_customized_name:skuAttributes[i].attribute_customized_name,
						// attribute_customized_pic:skuAttributes[i].attribute_customized_pic,
						// attribute_key_attribute:skuAttributes[i].attribute_key_attribute,
						// attribute_required:skuAttributes[i].attribute_required,
						attribute_name: skuAttributes[i].attribute_name_english,
					};
			_skuAttributes[skuAttributes[i].attribute_id].value_list = new Array;
			
			var options = {};
			options.onchange = 'collectSelected(' + skuAttributes[i].attribute_id + ', this);';
			options.name = 'sku_attributes[' + skuAttributes[i].attribute_id + '][]';
			html += '<tr id="attr_' + skuAttributes[i].attribute_id + '">' + "\n" + '<td class="leftColumn">' + "\n";
			html += '<label>' + skuAttributes[i].attribute_name_english + '：</label>' + "\n";
			html += '</td>' + "\n";
			html += '<td class="rightColumn">' + "\n" + generateHtml(skuAttributes[i], options, 'sku') + "\n" + '<div id="customAttributesBox"></div>' + '</td>' + "\n";
			html += '</tr>' + "\n";
		}
		// console.log(skuAttributes);
		// console.log(_skuAttributes);		
		// console.log(commonAttributes);
		// console.log(topCategoryList);
		html += '</tbody></table>' + "\n";
		//如果为多属性才显示
		if (skuAttributes != '' && _publishType != 1) {
			// $('#skuAttrRow').show();
			$('#skuAttributes').html(html);
		}

		//生成分类属性
		var html = '<table id="commonAttributeTable" class="attributesTable"><tbody>' + "\n";

		//显示分类商品类型
		html += '<tr id="item_type">' + "\n" + '<td class="leftColumn" style="text-align:left;">' + "\n";
		html += '<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product ItemType');?> ：</span>' + "\n";
		html += '</td>' + "\n";
		html += '<td class="rightColumn"><input  name="item_type" class="textInput" type="text" style="color:gray;width:250px;" readonly value="' + item_type + '" size="35" /></td>' + "\n";
		html += '</tr>' + "\n";

		//显示分类产品类型
		html += '<tr id="cate_product_type">' + "\n" + '<td class="leftColumn" style="text-align:left;">' + "\n";
		html += '<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product Type');?> ：</span>' + "\n";
		html += '</td>' + "\n";
		html += '<td class="rightColumn"><input name="cate_product_type" class="textInput" type="text" style="color:gray;width:250px;" readonly value="' + cate_product_type + '" size="35" /></td>' + "\n";
		html += '</tr>' + "\n";		

		if (commonAttributes != ''){
			var options = {};
			options.name = 'xsd_product_type_select';
			html += '<tr id="xsd_product_type">' + "\n" + '<td class="leftColumn" style="text-align:left;">' + "\n";
			html += '<span class="attributeFlag required">*</span><span>分类类型 ：</span>' + "\n";
			html += '</td>' + "\n";
			html += '<td class="rightColumn">' + "\n" + generateHtml(commonAttributes, options, 'select') + '</td>' + "\n";
			html += '</tr>' + "\n";
		}else{
			//列举所有XSD顶级分类供选择，然后联动下级分类类型
			var options = {};
			options.onchange = 'findProductType(this)';
			options.name = 'xsd_product_type_select';
			html += '<tr id="xsd_product_type">' + "\n" + '<td class="leftColumn" style="text-align:left;">' + "\n";
			html += '<span class="attributeFlag required">*</span><span>顶级分类 ：</span>' + "\n";
			html += '</td>' + "\n";
			html += '<td class="rightColumn">' + "\n" + generateHtml(topCategoryList, options, 'selectall') + '<div id="xsd_sub_select"></div></td>' + "\n";
			html += '</tr>' + "\n";
		}

		html += '</tbody></table>' + "\n";
		// if (commonAttributes != '') {
			$('#commomAttrRow').show();
			$('#commonAttributes').append(html);
		// }

		//获取产品价格
		// getProductPrice();
		//如果是多属性，生成多属性产品
		generateVariationProduct();	
	}, 'json');
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
	var type_tmp = '';
	if (typeof(data.attribute_showtype_value) == 'undefined') {	
		type_tmp = type;
	}else{
		type_tmp = data.attribute_showtype_value;
	}
	switch(type_tmp) {
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
		case 'select':
		case 'selectall':
			return generateDropdownSelectHtml(data, options, type);
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
					attribute_value_name: list[i].attribute_value_en_name,
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
		html += '<label for="value_id_' + list[i].attribute_value_id + '">' + list[i].attribute_value_en_name + '</label>' + "\n";
		//if (data.attribute_name_english == 'Color')
			//html += '<div style="display:inline-block;border:1px #ccc solid;width:10px;height:10px;background:'+list[i].attribute_value_en_name+';"></div>' + "\n";
		html += '</li>' + "\n";
	}
	html += '</ul>' + "\n";
	return html;
}
//生成dropdown list html
function generateDropdownListHtml(data, options, type) {
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

//生成分类属性select html
function generateDropdownSelectHtml(data, options, type) {
	var list = data;
	var havePublish = "<?php echo $publishParams['publish_product_readonly']; ?>";
	var point = '';
	var html = '<div><select ';
	if (havePublish == '1'){
		html += ' disabled ';
	}
	var checkedAttribute = '';
	for (var j in options) {
		html += ' ' + j + '="' + options[j] + '"';
	}
	html += '>' + "\n";
	if (type != 'sub_select'){
		html += '<option value=""><?php echo Yii::t('system', 'Please Select');?></option>' + "\n";
	}
	var k = 0;
	for (var i in list) {
		//默认选中第一项
		if (k == 0){
			if (type != 'selectall'){
				checkedAttribute = ' selected="selected"';
			}
		}else{
			checkedAttribute = '';
		}
		if (type == 'selectall' || type == 'sub_select'){
			html += '<option ' + checkedAttribute + ' value="' + list[i].id + '">' + list[i].title + '</option>' + "\n";		
		}else{
			if (list[i].parent_title.length > 0){
				if(list[i].title.length > 0) point = '.';
				html += '<option ' + checkedAttribute + ' value="' + list[i].id + '">' + list[i].parent_title + point + list[i].title + '</option>' + "\n";				
			}else{
				html += '<option ' + checkedAttribute + ' value="' + list[i].id + '">' + list[i].title + '</option>' + "\n";			
			}
			
		}		
		k++;
	}
	html += '</select></div>' + "\n";
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
				//html += '<label>' + generateFlagHtml(data[i]) + data[i].attribute_name_english + '：</label>' + "\n";
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


//查找分类类型
function findProductType(obj) {
	$('.xsd_sub_select').remove();
	var topid = $(obj).find('option:selected').val();
	if (topid != ''){
		$.ajax({
			type:'post',
			url:'amazon/amazoncategoryxsd/getproducttypelist/topid/' + topid,
			dataType:'json',
			success:function(data){
				// console.log(data);
				var html = '';
				if (data.length > 0) {
					var options = {};
					options.name = 'xsd_sub_select';
					html += '<tr class="xsd_sub_select">' + "\n" + '<td class="leftColumn"  style="text-align:left;">' + "\n";
					html += '<span class="attributeFlag required">*</span><label>分类类型 ：</label>' + "\n";
					html += '</td>' + "\n";
					html += '<td class="rightColumn">' + "\n" + generateDropdownSelectHtml(data, options, 'sub_select') + '</td>' + "\n";
					html += '</tr>' + "\n";
				    $(obj).parent().parent().parent().after(html);
				}
				return false;
			}
		});
	}
	return false;
}

//保存刊登数据
function saveInfo(){
	var tabid            = $('ul.navTab-tab li.selected').attr('tabid');

	//更新保存时，select只读（disabled）时是获取不到值，所以要在提交时设置disabled=false，以便获取到selected值
	$("select[name=xsd_product_type_select]").attr("disabled",false);
	$("select[name=xsd_sub_select]").attr("disabled",false);
	$("select[name$='[product_id_type]']").attr("disabled",false);
	

	//获取分类类型文本
	var product_type_id  = $('#xsd_product_type').find('option:selected').val();
	var product_type     = $('#xsd_product_type').find('option:selected').text();			
	var sub_product_type = $('select[name=xsd_sub_select]').find("option:selected").text();

	$('input[name=xsd_product_type_select_text]').val('');
	if (product_type_id != ''){
		if(typeof(sub_product_type) != 'undefined' && sub_product_type != '') product_type = product_type + '.' + sub_product_type;
		$('input[name=xsd_product_type_select_text]').val(product_type);
	}
	$.ajax({
			type: 'post',
			url: $('form#product_add').attr('action'),
			data:$('form#product_add').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
					setTimeout(function(){          
						$('form#product_add a.display_list').click();
						navTab.closeTab(tabid);
						navTab.closeTab('_blank');
						navTab.openTab(result.navTabId, '/amazon/amazonproductadd/list', {title: 'Amazon待刊登列表'});
					}, 300);
				}
			},
			dataType:'json'
	});
}
//自动填写其他账号对应字段
function autoFill(obj) {
	var text = $(obj).val();
	$('input[group=' + $(obj).attr('group') + ']').each(function(){
		$(this).val(text);
	});
}

//计算利润信息
function loadPriceInfo(self){
	var categoryID = $('input[name=category_id]').val();
	var sku = $('input[name=publish_sku]').val();
	var accountID = $(self).attr('account_id');
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
					//$('input[name=product_price\\[' + i + '\\]]').val(item.salePrice);
					$('input[name=product_price\\[' + i + '\\]]').removeAttr('disabled');
					$('input[name=product_price\\[' + i + '\\]]').next('.profitDetail').html('&nbsp;&nbsp;<font style="color:red;"><?php echo Yii::t('common', 'Profit');?>:'+item.profit+',<?php echo Yii::t('common', 'Profit Rate');?>:'+item.profitRate+'.</font>'
						+'<a href="javascript:;" onClick="alertMsg.confirm(\''+item.desc+'\')"><?php echo Yii::t('common', 'Show Detail');?></a>');
				});
			} else {
				alertMsg.error('<?php echo Yii::t('amazon_product', 'Get Product Price Error');?>');
			}
		},
		dataType:'json'
	});	
}

//获取多属性产品价格信息
function getVariationProductPrice(row) {
	var categoryID = $('input[name=category_id]').val();
	var sku = $.trim($('input[name=variation_skus\\[' + row + '\\]]').val());
	if (sku == '')
		return false;
	var account_id = 0;
	var data = 'category_id=' + categoryID + '&publish_sku=' + sku + '&' + 'account_id[]=' + account_id;
	var priceElement = $('input[name=variation_price\\[' + row + '\\]]');
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
				alertMsg.error('<?php echo Yii::t('amazon_product', 'Get Product Price Error');?>');
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

//展示XSD结构数据
function showXSDSelected(attributeID) {
	$.ajax({
		type:'post',
		url:'amazon/amazoncategoryxsd/getproducttypeinfo/' + attributeID,
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
				alertMsg.error('<?php echo Yii::t('amazon_product', 'Get Product Info Error');?>');
			}
		},
	});
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
	//如果是多属性刊登或者单品产品刊登到有SKU属性的分类且同一属性选择了两个或者两个以上属性
	if (_publishType == 2 || variationPublishFlag) {
		//多属性产品列表表头
		html += '<tr>' + "\n";
		var isCustomName = false;
		for (var i in columns) {
			html += '<th><span>' + _skuAttributes[columns[i]].attribute_name + '</span></th>' + "\n";
			if (_skuAttributes[columns[i]].attribute_customized_name == 1)
				isCustomName = true;
		}
		// if (isCustomName)
		// 	html +='<th><span><?php echo Yii::t('amazon_product', 'Custom Name');?></span></th>' + "\n"; 
		// html += '<th><span><?php echo Yii::t('amazon_product', 'SKU');?></span></th>' + "\n";
		// html += '<th><span><?php echo Yii::t('amazon_product', 'Prodcut ID');?></span></th>' + "\n";
		// html += '<th><span><?php echo Yii::t('amazon_product', 'Price');?></span>&nbsp;&nbsp;<input type="text" id="batchSetSkuPrice" style="width:80px;" />&nbsp;&nbsp;<a href="javascript:void(0);" onclick="batchSetSkuPrice(this)">设置</a></th>' + "\n";
		// html += '</tr>';
		
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
			customName = $('tr.variation_row' + rowClass).find('input[name^=variation_custom_name]').val();
			if (typeof(price) == 'undefined')
				price = '';
			if (typeof(priceDetials) == 'undefined')
				priceDetials = '';
			if (typeof(customName) == 'undefined')
				customName = '';
			
			html += '<tr class="variation_row' + rowClass + '">' + "\n" + columnHtml;
			if (isCustomName)
				html += '<td><input type="text" name="variation_custom_name[' + i + ']" value="' + customName + '" size="18" /></td>' + "\n";
			//html += '<td><input type="text" onchange="getVariationProductPrice(' + i + ')" name="variation_skus[' + i + ']" value="' + sku + '" size="22" /></td>' + "\n";
			html += '<td><input type="text"  name="variation_skus[' + i + ']" value="' + sku + '" size="22" /></td>' + "\n";
			html += '<td><div style="float:left;margin-right:2px;"><select name="variation_product_id_type[' + i + ']"><option value="4" selected>UPC</option><option value=1>EAN</option><option value=2>GCID</option><option value=3>GTIN</option></select></div><div style="float:left;"><input type="text" name="variation_product_id[' + i + ']" value="" size="22" /></div></td>' + "\n";
			//html += '<td><input type="text" class="sku_variation_price"  onblur="loadPriceInfo(this)" name="variation_price[' + i + ']" value="' + price + '" size="8" />';
			html += '<td><input type="text" class="sku_variation_price"  name="variation_price[' + i + ']" value="' + price + '" size="8" />';
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
	html += '<th><?php echo Yii::t('amazon_product', 'Custom Attribute Name');?></th>' + "\n";
	html += '<th><?php echo Yii::t('amazon_product', 'Custom Attribute Image');?></th>' + "\n";
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
	html += '<td><input size="32" type="text" name="custom_attribute_name[]" value="<?php echo Yii::t('amazon_product', 'Attribute Name');?>" onblur="if(this.value == \'\') this.value=\'<?php echo Yii::t('amazon_product', 'Attribute Name');?>\';" onfocus="if(this.value==\'<?php echo Yii::t('amazon_product', 'Attribute Name');?>\') this.value=\'\'" /></td>' + "\n";
	html += '<td><input size="32" type="text" name="custom_attribute_value[]" value="<?php echo Yii::t('amazon_product', 'Attribute Value');?>" onblur="if(this.value == \'\') this.value=\'<?php echo Yii::t('amazon_product', 'Attribute Value');?>\';" onfocus="if(this.value==\'<?php echo Yii::t('amazon_product', 'Attribute Value');?>\') this.value=\'\'" /></td>' + "\n";
	html += '<td><a href="javascript:void(0)" onclick="deleteCustomAttributeRow(this)"><?php echo Yii::t('amazon_product', 'Delete Row');?></a></td>' + "\n";
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

function DescriptionLimit(obj){

	var val=$(obj).val().length;
	if(val>2000){
		alert("至多输入2000个字符！");
		$(obj).val($(obj).val().substring(0,20000))
		}
}

//新增子SKU
function addSubSKUProduct(){
	var sku = $.trim($("#inputsku").val());
	var accountID = $.trim($("#cur_account_id").val());
	if (sku.length == 0){
		alertMsg.error("输入的子SKU不能为空");
		return false;
	}else{
		var repeat_flag = 0;
		$.each($('#subskuAttributeTable tbody tr').find("input[name$='[sku]']"),function(i,item){
			var cur_sku = $.trim($(item).val());
			if (sku == cur_sku){
				alertMsg.error("不能重复子SKU");
				repeat_flag = 1;
				return false;
			}
		});
		if (repeat_flag == 0){
			//判断该SKU是否为单品或者为子SKU，否则提示错误，其次判断是否侵权、停售产品
			var data = 'sku=' +sku+ '&account_id=' +accountID;
			$.ajax({
					type: 'post',
					url: '<?php echo Yii::app()->createUrl('amazon/amazonproductadd/validatesku');?>',
					data:data,
					dataType:'json',
					success:function(result){
						if(result.statusCode != '200'){
							alertMsg.error(result.message);
						}else{
							var sell_sku          = result.sell_sku;	//获取加密SKU
							var add_select_images = result.add_select_images;	//获取已选中的主副多张图片字符串
							var sku_product_id    = result.product_id;
							var tab               = $('#subskuAttributeTable tbody tr').eq(-1).clone();
							var selectimage_url   = '/amazon/amazonproductadd/selectimage/subsku/'+sku+'/account_id/'+accountID+'/readonly/0';
															
							tab.find("input[name$='[sku]']").val(sku);	//设置SKU值

							tab.find("input[name$='[image]']").val(add_select_images);	//设置图片字符串
							tab.find("input[name$='[image]']").removeClass();	//删除替换class
							tab.find("input[name$='[image]']").addClass("image_"+sku_product_id);

							tab.find("input[name$='[sell_sku]']").val(sell_sku);
							tab.find("[name='sku_value']").html(sku);							
							tab.attr('id','attr_'+Math.ceil(Math.random()*10000));
							tab.find("input[name$='[product_id]']").val('');							
							tab.find(".hide").attr('class','btn');
							tab.find(".deletesub .btn").attr("onclick","deletetr(this)");
							tab.find(".selectimage").attr("href",selectimage_url);							
							tab.find(".updateStatus").empty();
							tab.find("span").empty();

							//替换input的name值
							$.each(tab.find("input[name^='skuinfo']"),function(j,ele){
								var reg = new RegExp('\\[(.+?)\\]');
								var v = $(ele).attr("name").replace(reg,"[" +sku+ "]");
								$(ele).attr("name",v);
							});

							//替换select的name值
							var reg = new RegExp('\\[(.+?)\\]');
							var v = tab.find("select[name$='[product_id_type]']").attr("name").replace(reg,"[" +sku+ "]");											
							tab.find("select[name$='[product_id_type]']").attr("name",v);

							//必须初始化DWZ，动态新增的记录才能有查找带回的效果
							tab.find(".selectimage").parent().initUI();	

							tab.appendTo('#subskuAttributeTable');
							
						}
					}					
			});
		}
	}	
}

//批量更新所有子SKU价格
function batchUpdateSubProduct(){
	var m_price = parseFloat($.trim($("#m_price").val()));
	var s_price = parseFloat($.trim($("#s_price").val()));
	if (isNaN(s_price)){
		s_price = 0;
	}

	if (m_price.length == 0 || m_price == 0){
		alertMsg.error("输入的子SKU价格不能为空或为零");
		return false;
	}else{
		if(!/^\d*(?:\.\d{0,2})?$/.test(m_price) || (s_price > 0 && !/^\d*(?:\.\d{0,2})?$/.test(s_price))){
			alertMsg.error("请正确输入价格：支持小数点后两位的金额");
			return false;
		}else{
			if (m_price < s_price){
				alertMsg.error("促销价格必须小于等于标准价格");
				return false;
			}{
				//标准价格
				$.each($('#subskuAttributeTable tbody tr').find("input[name$='[market_price]']"),function(i,item){
					//不是只读属性，才能更新价格
					if (typeof($(item).attr('readonly')) == 'undefined'){
						$(item).val(m_price);
					}				
					//促销价格
					var promotion = $(item).parent().next().find("input[name$='[price]']");
					if (promotion){
						if (typeof(promotion.attr('readonly')) == 'undefined'){
							promotion.val(s_price);
						}				
					}				
				});
			}
		}
	}	
}

//删除新增子SKU
function deletetr(self){
	if ($("tr.subskulist").length <= 1){
		alertMsg.error('最后一个子SKU不能删除，请先添加别的子SKU再删除');
		return false;
	}else{
		$(self).parent().parent().parent().remove();
	}
}

//删除已入库的子SKU
function del_variant(self, addId, vid){
	if($("tr.subskulist").length <= 1){
		alertMsg.error("至少保留一条！");
		return false;
	}
	if(confirm('确定要删除？删除将不可恢复！')){
		$.ajax({
			type: 'post',
			url: '<?php echo Yii::app()->createUrl('amazon/amazonproductadd/delvariant');?>',
			data:{'add_id':addId, 'variation_id':vid},
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					$(self).parent().parent().parent().remove();
				}
			},
			dataType:'json'
		});
	}
}

$("td.reuploadstatus").live('click', function(event){
	var vid = $(this).attr('variation_id');
	if (vid){
		var data = 'variation_id=' + vid;
		$.ajax({
			type: 'post',
			url: '<?php echo Yii::app()->createUrl('amazon/amazonproductadd/reuploadstatus');?>',
			data:data,
			dataType:'json',
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
				}
			}					
		});
	}
	// event.stopPropagation();
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
					'id'                     => 'product_add',
					'enableAjaxValidation'   => false,  
					'enableClientValidation' => true,
					'clientOptions'          => array(
						'validateOnSubmit'       => true,
						'validateOnChange'       => true,
						'validateOnType'         => false,
						'afterValidate'          =>'js:afterValidate',
						'additionValidate'       =>'js:checkResult',
					),
					'action'                 => Yii::app()->createUrl('amazon/amazonproductadd/savedata'), 
					'htmlOptions'            => array(        
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
                                  		CHtml::link($publishParams['sku'], 'products/product/productview/sku/'.$publishParams['sku'], 
                    			array('style'=>'color:blue;','target'=>'dialog','width'=>'900','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
                    			?></li> 
    				                <!--<li><font class="bold"><?php echo Yii::t('lazada', 'Listing Type')?>：</font><?php echo $publishParams['publish_type']['text'];?></li>-->
    				                <li><font class="bold"><?php echo Yii::t('lazada', 'Listing Mode')?>：</font><?php echo $publishParams['publish_mode']['text'];?></li>
				                </ul>
				                <input type="hidden" name="publish_sku" value="<?php echo $publishParams['sku'];?>" />
				                <input type="hidden" name="publish_site" value="<?php echo $publishParams['publish_site'];?>" />
				                <input type="hidden" name="publish_type" value="<?php echo $publishParams['publish_type']['id'];?>" />
				                <input type="hidden" name="publish_mode" value="<?php echo $publishParams['publish_mode']['id'];?>" />
								<input type="hidden" name="cur_account_id" id="cur_account_id" value="<?php echo $cur_account_id?>" />
				                <input type="hidden" name="xsd_product_type_select_text" value="" />

				                <?php if ($action == 'update') { ?>
				                <input type="hidden" name="action" value="update" />
				                <input type="hidden" name="id" value="<?php echo $addID;?>" />
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
				                    <div><strong><?php echo Yii::t('amazon_product', 'Main Images');?></strong></div>
									<?php $count = 1; $selected = 0;?>
                                	<?php if (isset($skuImg['zt']) && $skuImg['zt']) { ?>
	                                	<div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
	                                    <?php foreach($skuImg['zt'] as $k=>$image):?>
	                                    
	                                    <?php if($action == 'update' && in_array($k, $selectedImages['zt'])):?>
	                                    <?php $selected++;?>
	                                    <?php endif;?>
	                                    
	                                    <div style="position:relative;" class="aliexpress_image">
	                                        <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
	                                    </div>
	                                    
	                                    <?php $count++;?>
	                                    <?php endforeach;?>
	                             		</div>
                                    <?php } ?>


                                    <div style="clear:both;"></div>
                                </div>
                               
                                
                                <div class="page unitBox ftimgs">
                                    <div><strong><?php echo Yii::t('amazon_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php if (!empty($skuImg)) { ?>
                                        <?php $count = 0;?>
                                        <?php foreach($skuImg['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
                                            
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
				            <td width="15%" style="font-weight:bold;">
				            	<?php echo Yii::t('amazon_product', 'Product Category');?>
				            	<br /><br />
				            	<div style="line-height: 150%;font-weight: normal;">
					            	目前刊登失败的类目：<br />
					            	Clothing(ClothingAccessories)<br />Shoes
				            	</div>
				            </td>
				            <td>
				            	<div class="categoryBox">
				            		<div class="tabHeader">
				            			<ul class="tabHeaderList">
				            			<?php if (!empty($historyCategoryList)) { ?>
				            				<li class="tab1 on"><a href="#"><?php echo Yii::t('amazon_product', 'History Category');?></a></li>
				            			<?php } ?>	
				            				<li class="tab2"><a href="#"><?php echo Yii::t('amazon_product', 'Search Category');?></a></li>
				            				<li class="tab3 <?php if(empty($historyCategoryList)) echo 'on'; ?>"><a href="#"><?php echo Yii::t('amazon_product', 'Choose Category');?></a></li>
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
					            				<input onfocus="if (this.value == _keywords) this.value=''" onblur="if (this.value == '') this.value=_keywords;" size="125" class="textInput" type="text" name="search_keywords" value="" />&nbsp;&nbsp;
					            				<a class="btn" href="javascript:void(0)" onclick="searchKeywords()"><?php echo Yii::t('amazon_product', 'Search');?></a>
				            				</div>
				            				<div id="categoryListBox">
				            					<select class="categoryList" name="category_list_search" size="16" style="min-width:455px" onchange="setCategory(this)">
				            					</select>	            					
				            				</div>
				            			</div>
				            			<div id="tab3" class="tabContent" <?php if(empty($historyCategoryList)) {?>style="display: block"<?php } ?>>
				            				<select size="16" style="min-width:250px;" name="category_list_choose_level_1" onclick="findSubCategory(this)">
				            				<?php foreach($chooseCategoryList as $cateID => $cateName) { ?>
				            					<option value="<?php echo $cateID;?>"><?php echo $cateName;?></option>
				            				<?php } ?>
				            				</select>
				            			</div>
				            		</div>
				            		<div class="tabFooter">
				            			<input size="125" readonly class="textInput" type="text" name="category_name" value="<?php echo end($defaultHistoryCategory);?>" />
				            			<input type="hidden" value="<?php echo key($defaultHistoryCategory);?>" name="category_id" />
				            			<a href="#" onclick="<?php if($publishParams['publish_product_readonly'] != 1) echo 'findCategoryAttributes()'; ?>" class="btn" id="categoryConfirmBtn"><?php echo Yii::t('amazon_product', 'Confirm Choose Category');?></a>
				            			<!--<a href="#" onclick="syncCategoryAttributes()" class="btn"><?php echo Yii::t('aliexpress_product', '同步该类目属性');?></a>-->
				            		</div>
				            	</div>
				            </td>
				        </tr>
				        <!-- 类别显示END -->

                        <!-- 分类属性显示START -->               
						<tr id="commomAttrRow" style="display:none">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Category Attribute');?></td>
				            <td>
				                <div id="commonAttributes" style="padding:5px 25px;">
				                <?php if ($action == 'update') { ?>
				                <table id="commonAttributeTable" class="attributesTable">
									<tbody>								
				                		<?php foreach ($commonAttributes as $commonAttribute) { ?>
										<tr id="attr_<?php echo $commonAttribute['attribute_id'];?>">
											<td class="leftColumn">
												<?php echo ($commonAttribute['attribute_required'] == 1) ? ('<span class="attributeFlag required">*</span>' . "\n") : (($commonAttribute['attribute_key_attribute'] == 1) ? '<span class="attributeFlag required">！</span>' . "\n" : '');?>
												<label><?php echo $commonAttribute['attribute_name_english'] . '(' . $commonAttribute['attribute_name_Chinese'] . ')';?></label>
											</td>
											<td class="rightColumn">
												<?php //foreach ($skuAttribute as $valueList) { ?>
												<?php echo $this->renderPartial('application.modules.aliexpress.components.views.generate_html', array('data' => $commonAttribute, 'type' => 'common', 'sku'=>$publishParams['sku']));?>
												<?php //} ?>
												<div id="customAttributesBox"></div>
											</td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
				                <?php } ?>				                
				                </div>
				                <!--添加自定义属性
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
				                -->
				            </td>
				        </tr> 
                        <!-- 分类属性显示END -->  	

                        <!-- sku分类显示START -->
                        <tr id="skuAttrRow"<?php echo ($action != 'update') ? ' style="display:none"' : '';?>>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('amazon_product', 'Sale Attribute');?></td>
				            <td>
				                <div id="skuAttributes">
				                <?php if ($action == 'update') { ?>
				                <table id="skuAttributeTable" class="attributesTable">
									<tbody>
				                		<?php foreach ($skuAttributes as $skuAttribute) { ?>
										<tr id="attr_<?php echo $skuAttribute['attribute_id'];?>">
											<td class="leftColumn">
												<label><?php echo $skuAttribute['attribute_name_english'];?></label>
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
                        <!-- sku分类显示END -->                        			     

                        <!-- 子SKU属性显示START -->
                        <tr id="subSKUAttrRow">
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('aliexpress_product', 'Sale Attribute');?></td>
				            <td>
				                <div id="skuAttributes">				               
					                <table id="subskuAttributeTable" class="attributesTable">
					                	<thead>
					                		<tr>
					                			<!--<th><input type="checkbox" id="wish_add_all_select" checked onclick="wish_product_add_func.selectAllSku(this)"/></th>-->
					                			<th><?php echo Yii::t('amazon_product', 'Sku');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'seller SKU');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'ProductID');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'Inventory');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'Market Recommand Price');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'Sales Price');?></th>
					                			<th><?php echo Yii::t('amazon_product', 'Sales Date');?></th>
					                			<!-- <th><?php echo Yii::t('amazon_product', 'Shipping');?></th> -->
					                			<th><?php echo Yii::t('amazon_product', 'Image');?></th>
					                			<?php if ($action == 'update') { ?>
					                				<th><?php echo Yii::t('amazon_product', 'UPLOAD STATUS');?></th>
					                			<?php } ?>
					                			<th><?php echo Yii::t('amazon_product', 'Oprator');?></th>
					                		</tr>
					                	</thead>
										<tbody>
					                		<?php if($listingSubSKU):?>
					                		<?php foreach ($listingSubSKU as $val):?>				                		
											<tr id="attr_<?php echo $val['product_id'];?>" class="subskulist">

												<td>
													<div name="sku_value"><?php echo $val['sku'];?></div>
													<input type="hidden" name="skuinfo[<?php echo $val['sku'];?>][sku]" value="<?php echo $val['sku'];?>"/>
													<input type="hidden" name="skuinfo[<?php echo $val['sku'];?>][image]" class="image_<?php echo $val['product_id'];?>" value='<?php if(isset($val['skuInfo']['image'])) echo $val['skuInfo']['image']; ?>' />
													<input type="hidden" name="skuinfo[<?php echo $val['sku'];?>][shipping]" value="<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly'; ?> class="ship_to_price_info"/>
												</td>
				                				<td>
						                			<input style="width:130px;" type="text" name="skuinfo[<?php echo $val['sku'];?>][sell_sku]" <?php if(isset($val['skuInfo']['sub_product_readonly']) && $val['skuInfo']['sub_product_readonly'] == 1) echo 'readonly'; ?> value="<?php if(isset($val['skuInfo']['sell_sku'])) echo $val['skuInfo']['sell_sku']; else echo 1;?>" class="required"/>				
					                			</td>											
					                			<td>
													<div style="float:left;margin-right:2px;">
														<select name="skuinfo[<?php echo $val['sku'];?>][product_id_type]" <?php if(isset($val['skuInfo']['sub_product_readonly']) && $val['skuInfo']['sub_product_readonly'] == 1) echo 'disabled'; ?> style="padding: 7px 0">
														<?php foreach($publishParams['amazon_product_type'] as $k => $type): ?>
															<option value="<?php echo $k; ?>" <?php if(isset($val['skuInfo']['product_id_type']) && $k == $val['skuInfo']['product_id_type']){echo 'selected';} else {if($k == 4){echo 'selected';}}?>><?php echo $type; ?></option>
														<?php endforeach; ?>	
														</select>
													</div>
													<div style="float:left;"><input style="width:85px;" type="text" name="skuinfo[<?php echo $val['sku'];?>][product_id]" <?php if(isset($val['skuInfo']['sub_product_readonly']) && $val['skuInfo']['sub_product_readonly'] == 1) echo 'readonly'; ?> value="<?php if(isset($val['skuInfo']['product_id'])) echo $val['skuInfo']['product_id']; ?>" size="22" /></div>				                				
					                			</td>											
					                			<td>
						                			<input type="text" name="skuinfo[<?php echo $val['sku'];?>][inventory]" value="<?php if(isset($val['skuInfo']['inventory'])) echo $val['skuInfo']['inventory']; else echo 1;?>" <?php if(isset($val['skuInfo']['sub_inventory_readonly']) && $val['skuInfo']['sub_inventory_readonly'] == 1) echo 'readonly'; ?> class="required"/>
					                			</td>
					                			<td>
						                			<input type="text" name="skuinfo[<?php echo $val['sku'];?>][market_price]" value="<?php if(isset($val['skuInfo']['market_price'])) echo $val['skuInfo']['market_price']; else echo $val['skuInfo']['product_cost'];?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly title="上传中或是上传成功，不能更新"'; ?> class="required"/>
					                			</td>				                			
					                			<td>
					                				<input type="text" name="skuinfo[<?php echo $val['sku'];?>][price]" value="<?php echo $val['skuInfo']['price'];?>" class="sale_price_info" sku="<?php echo $val['sku'];?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly title="上传中或是上传成功，不能更新"'; ?> />
					                				<span><?php echo isset($val['skuInfo']['price_error'])?$val['skuInfo']['price_error']:'';?></span>
					                				<span style="color:red;" class="profit_info"><?php if(isset($val['skuInfo']['price_profit'])) echo $val['skuInfo']['price_profit']; ?></span>			                				
					                			</td>
					                			<td>
					                				<input class="date textInput" datefmt="yyyy-MM-dd HH:mm:ss" type="text" name="skuinfo[<?php echo $val['sku'];?>][sales_start_date]" value="<?php if(isset($val['skuInfo']['sale_start_time'])) echo $val['skuInfo']['sale_start_time']; ?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly'; ?> id="gmt_create_0" style="margin-right:10px;" >
													<input class="date textInput" datefmt="yyyy-MM-dd HH:mm:ss" type="text" name="skuinfo[<?php echo $val['sku'];?>][sales_end_date]" value="<?php if(isset($val['skuInfo']['sale_end_time'])) echo $val['skuInfo']['sale_end_time']; ?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly'; ?> id="gmt_create_1">			                				
					                			</td>
					                			<!--
					                			<td>
						                			<input type="text" name="skuinfo[<?php echo $val['sku'];?>][shipping]" value="<?php if(isset($val['skuInfo']['shipping'])) echo $val['skuInfo']['shipping']; else echo 0.00;?>" <?php if(isset($val['skuInfo']['sub_price_readonly']) && $val['skuInfo']['sub_price_readonly'] == 1) echo 'readonly'; ?> class="ship_to_price_info"/>
					                			</td>
					                			-->	
					                			<td>
					                				<div class="categoryBox"><a href="<?php echo Yii::app()->createUrl('amazon/amazonproductadd/selectimage/subsku/'.$val['sku'].'/account_id/'.$cur_account_id.'/readonly/'.$val["skuInfo"]["sub_image_readonly"]);?>" class="btn selectimage" style="background: #1871dd;color:white;" lookupGroup="" lookupPk="category_id" width="800" height="600" >选 择</a></div>
					                			</td>	
					                			<?php if ($action == 'update' && isset($val['skuInfo']['sub_upload_status'])) { ?>
						                			<td>
							                			<div class="updateStatus">
								                			<?php echo $val['skuInfo']['sub_upload_status']; ?>
														</div>							                			
						                			</td>
					                			<?php } ?>					                					                			
					                			<td>
						                			<div class="categoryBox deletesub" style="width:80px;">
							                			<?php if ($action == 'update') { ?>
							                				<a href="#" onclick="del_variant(this, '<?php echo $addID;?>', '<?php echo $val['id'];?>')" class="<?php if(isset($val['skuInfo']['sub_have_publish_flag']) && $val['skuInfo']['sub_have_publish_flag'] == 1){echo 'hide';} else {echo 'btn';} ?>">删 除</a>
							                			<?php }else{ ?>
							                				<a href="#" onclick="deletetr(this)" class="btn">删 除</a>
							                			<?php } ?>	
						                			</div>
					                			</td>				                			
											</tr>
											<?php endforeach;?>										
											<?php endif;?>
										</tbody>
									</table>				             
				                </div>
				                <div id="productVariations">
				                </div>
				                <div id="addSubSKU" class="categoryBox" style="padding:10px 10px 45px 10px;">	
				                	<input type="text" id="inputsku" placeholder="输入子SKU" name="inputsku" />				                	
				                	<a href="#" onclick="addSubSKUProduct()" class="btn" id="categoryConfirmBtn">添加子SKU</a>
				                </div>
				                <div id="batchUpdate" class="categoryBox" style="padding:10px 10px 45px 10px;">	
				                	<input type="text" id="m_price" placeholder="标准价格（小数点后两位）" name="m_price" style="margin-right:10px;" />				                	
				                	<input type="text" id="s_price" placeholder="促销价格（小数点后两位）" name="s_price" />
				                	<a href="#" onclick="batchUpdateSubProduct()" class="btn" id="categoryConfirmBtn" title="批量更新所有子SKU对应价格（上传中或是上传成功的子SKU价格不能更新）">批量更新价格</a>
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
                            	 			    <?php $k = 0;foreach($accountList as $accountID => $account):?>
                            	 				<li <?php echo $k==0 ? 'class="selected"' : '' ?>>
                            	 				    <a href="#"><span>&nbsp;&nbsp;<?php echo $account['account_name'];?>&nbsp;&nbsp;</span></a>
                            	 					<input type="hidden" name="account_id[]" value="<?php echo $cur_account_id?>" />
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
                             			    		<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product Title');?>：</span>
                             			    	</td>
                             			    	<td>
                             			    		<input type="text" group="subject" name="subject[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> value="<?php echo $account['product_title'];?>" onblur = "checkStrLength(this,128)" maxlength="150" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>                             	            
                            	            <tr>
                             			        <td>
                             			    		<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product Brand');?>：</span>
                             			    	</td>
                             			    	<td>
	                             			    	<input type="text" group="brand" name="brand[<?php echo $accountID?>]" value="<?php echo $account['product_brand'];?>" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> placeholder = "可用店铺或是账号名" onblur = "checkStrLength(this,128)" maxlength="128" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>  
                            	            <tr>
                             			        <td>
                             			    		<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product Manufacturer');?>：</span>
                             			    	</td>
                             			    	<td>
	                             			    	<input type="text" group="manufacturer" name="manufacturer[<?php echo $accountID?>]" value="<?php echo $account['manufacturer'];?>" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> placeholder = "可用店铺或是账号名" onblur = "checkStrLength(this,128)" maxlength="128" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>   
                            	            <!--
                            	            <tr>
                             			        <td>
                             			    		<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Add Seller SKU');?>：</span>
                             			    	</td>
                             			    	<td>
	                             			    	<input type="text" group="sell_sku" name="sell_sku[<?php echo $accountID?>]" value="<?php echo $account['sell_sku'];?>" onblur = "checkStrLength(this,128)" maxlength="128" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>                             	                                     	                 
                            	            -->
                            	            <tr>
                             			        <td>
                             			    		<span><?php echo Yii::t('amazon_product', 'Product MfrPartNumber');?>：</span>
                             			    	</td>
                             			    	<td>
	                             			    	<input type="text" group="mfr_partnumber" name="mfr_partnumber[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> placeholder = "为空则默认用加密SKU" value="<?php echo $account['mfr_partnumber'];?>" onblur = "checkStrLength(this,128)" maxlength="128" size="125"/>
                             			    		&nbsp;&nbsp;<span class="warn" style="color:red;line-height:22px;"></span>
                             			    	</td>
                            	            </tr>

                            	            <tr>
                             			        <td style="vertical-align: top;padding-top:20px;">
                             			    		<span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product BulletPoint');?>：</span>
                             			    	</td>
                             			    	<td>
	                             			    	<div><input group="keywords" onchange="autoFill(this)" type="text" name="keywords[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" maxlength="500" value="<?php if(isset($account['bullet_point'])) echo $account['bullet_point'];?>" size="125"/></div>
	                             			    	<div><input group="more_keywords1" onchange="autoFill(this)" type="text" class="textInput" style="float:none;" name="more_keywords1[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> maxlength="500" value="<?php if(isset($account['bullet_point1'])) echo $account['bullet_point1'];?>" size="125" /></div>
	                             			    	<div><input group="more_keywords2" onchange="autoFill(this)" type="text" class="textInput" style="float:none;" name="more_keywords2[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> maxlength="500" value="<?php if(isset($account['bullet_point2'])) echo $account['bullet_point2'];?>" size="125" /></div>
	                             			    	<div><input group="more_keywords3" onchange="autoFill(this)" type="text" class="textInput" style="float:none;" name="more_keywords3[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> maxlength="500" value="<?php if(isset($account['bullet_point3'])) echo $account['bullet_point3'];?>" size="125" /></div>
	                             			    	<div><input group="more_keywords4" onchange="autoFill(this)" type="text" class="textInput" style="float:none;" name="more_keywords4[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> maxlength="500" value="<?php if(isset($account['bullet_point4'])) echo $account['bullet_point4'];?>" size="125" /></div>
                             			    	</td>
                            	            </tr>
                            	            <tr>
                             			        <td style="vertical-align: top;padding-top:20px;">
                             			    		<span><?php echo Yii::t('amazon_product', 'Product Search Keywords');?>：</span>
                             			    	</td>
                             			    	<!--
                             			    	<td>
	                             			    	<div><input group="search_terms" onchange="autoFill(this)" type="text" name="search_terms[<?php echo $accountID?>]" style="float:none;" maxlength="800" value="<?php echo $account['search_terms'];?>" size="125"/></div>
                             			    	</td>
                             			    	-->
                             			    	<td>
	                             			    	<div><input group="search_terms" onchange="autoFill(this)" type="text" name="search_terms[<?php echo $accountID?>]" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" maxlength="200" value="<?php if(isset($account['search_terms'])) echo $account['search_terms'];?>" size="125"/></div>
	                             			    	<div><input group="search_terms1" onchange="autoFill(this)" type="text" class="textInput" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" name="search_terms1[<?php echo $accountID?>]" maxlength="200" value="<?php if(isset($account['search_terms1'])) echo $account['search_terms1'];?>" size="125" /></div>
	                             			    	<div><input group="search_terms2" onchange="autoFill(this)" type="text" class="textInput" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" name="search_terms2[<?php echo $accountID?>]" maxlength="200" value="<?php if(isset($account['search_terms2'])) echo $account['search_terms2'];?>" size="125" /></div>
	                             			    	<div><input group="search_terms3" onchange="autoFill(this)" type="text" class="textInput" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" name="search_terms3[<?php echo $accountID?>]" maxlength="200" value="<?php if(isset($account['search_terms3'])) echo $account['search_terms3'];?>" size="125" /></div>
	                             			    	<div><input group="search_terms4" onchange="autoFill(this)" type="text" class="textInput" <?php if($publishParams['publish_product_readonly'] == 1) echo 'readonly'; ?> style="float:none;" name="search_terms4[<?php echo $accountID?>]" maxlength="200" value="<?php if(isset($account['search_terms4'])) echo $account['search_terms4'];?>" size="125" /></div>
                             			    	</td>                             			    	
                            	            </tr>                             	            
                            	            <!--单品时，显示相关账号的SKU，UPC，价格等-->
                            	            <!--
                   	           				<?php if ($publishParams['publish_type']['id'] == AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE) { ?>
	                            	            <tr>
	                            	            	<td><span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product ID');?>：</span></td>
													<td>
														<div style="float:left;margin-right:2px;">
															<select name="product_id_type[<?php echo $accountID?>]" style="padding: 7px 0">
															<?php foreach($publishParams['amazon_product_type'] as $k => $val): ?>
																<option value="<?php echo $k; ?>" <?php if($account['product_id_type_selected'] == $k){echo 'selected';}elseif($k == 4){echo 'selected';}?>><?php echo $val; ?></option>
															<?php endforeach; ?>	
															</select>
														</div>
														<div style="float:left;"><input type="text" name="product_id[<?php echo $accountID?>]" value="<?php echo $account['product_id_text'];?>" size="22" /></div>
													</td>
	                            	            </tr>
	                            	            <tr id="fixedPriceRow">
	                            	            	<td><span class="attributeFlag required">*</span><span><?php echo Yii::t('amazon_product', 'Product Fixed Price');?>：</span></td>
	                            	            	<?php if ($action == 'update') { ?>
	                            	            		<td><input account_id="<?php echo $accountID;?>" type="text" name="product_price[<?php echo $accountID?>]" value="<?php echo $account['product_price'];?>" size="12" /><span class="profitDetail"><?php //添加卖价详情说明?></span></td>
	                            	            	<?php } else {?>
														<td><input account_id="<?php echo $accountID;?>" type="text" name="product_price[<?php echo $accountID?>]" value="" size="12" /><span class="profitDetail"><?php //添加卖价详情说明?></span></td>
	                            	            	<?php } ?>
	                            	            </tr>
	                            	            <tr id="salesPriceRow">
	                            	            	<td><span><?php echo Yii::t('amazon_product', 'Product Sales Price');?>：</span></td>
	                            	            	<?php if ($action == 'update') { ?>
	                            	            		<td><input account_id="<?php echo $accountID;?>" type="text" name="product_sales_price[<?php echo $accountID?>]" value="<?php echo $account['sale_price'];?>" size="12" /></td>
	                            	            	<?php } else {?>
														<td><input account_id="<?php echo $accountID;?>" type="text" name="product_sales_price[<?php echo $accountID?>]" value="" size="12" /></td>
	                            	            	<?php } ?>
	                            	            </tr>	 
	                            	            <tr id="salesDateRow">
	                            	            	<td><span><?php echo Yii::t('amazon_product', 'Product Sales Date');?>：</span></td>
													<td>
													<input class="date textInput" style="margin-right:10px;" datefmt="yyyy-MM-dd HH:mm:ss" type="text" name="sales_start_date[<?php echo $accountID?>]" value="<?php echo $account['sale_start_time'];?>" id="gmt_create_0">
													<input class="date textInput" datefmt="yyyy-MM-dd HH:mm:ss" type="text" name="sales_end_date[<?php echo $accountID?>]" value="<?php echo $account['sale_end_time'];?>" id="gmt_create_1">
													</td>
	                            	            </tr>	                            	                                    	                                       	           
                            	            <?php } ?>
                            	            -->
					                        <!-- 产品描述START -->
					                        <tr>
					                        	<td><?php echo Yii::t('amazon_product', 'Product Description');?></td>
					                        	<td>
					                        		<textarea rows="42" cols="22" name="detail[<?php echo $accountID?>]" class="productDescription" maxlength="2000" title="最多可输入2000个字符"><?php echo $account['description'];?></textarea>
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
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('amazon_product', 'Save Into List');?></a>&nbsp;
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
                        <a href="<?php echo Yii::app()->createUrl('amazon/amazonproductadd/list/sku/'.$publishParams['sku'].'/status/'.AmazonProductAdd::UPLOAD_STATUS_DEFAULT);?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('common','Product Add List');?></a>
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	
        	<div class="row imgArea" style="display:none;float:right;clear:none;width:9%;min-width:9%;">
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
	<?php }else{ ?>
		findCategoryAttributes(1);
	<?php } ?>	

	// if ($('input').attr('readonly') != undefined) {
	// 	$('input').css({'color':'blue'});
	// }

});
KindEditor.create('textarea.productDescription',{
	allowFileManager: true,
	<?php if($publishParams['publish_product_readonly'] == 1) { ?>
		readonlyMode : true,
	<?php } ?>
	width: '90%',
	height: '450',
	afterCreate : function() {
    	this.sync();
    },
    afterBlur:function(){
        this.sync();
    },
/*    afterChange : function() {
      $(this).html(this.count()); //字数统计包含HTML代码
      //$('.word_count2').html(this.count('text'));  //字数统计包含纯文本、IMG、EMBED，不包含换行符，IMG和EMBED算一个文字
      //////////
      //限制字数
      var limitNum = 10;  //设定限制字数
      var pattern = '还可以输入' + limitNum + '字'; 
      $('.word_surplus').html(pattern); //输入显示
      if(this.count('text') > limitNum) {
       pattern = ('字数超过限制，请适当删除部分内容');
       //超过字数限制自动截取
       var strValue = editor.text();
       strValue = strValue.substring(0,limitNum);
       editor.text(strValue);      
       } else {
       //计算剩余字数
       var result = limitNum - this.count('text'); 
       pattern = '还可以输入' +  result + '字'; 
       }
       $(this).html(pattern); //输入显示
      ////////
    }  */   
});
generateVariationProduct();
</script>