
<style>
<!--
.pageFormContent label{
	display: inline;
    float: none;
    width: auto;
}
#image_add table td li{line-height:20px;}
#image_add table td font.bold{font-weight:bold;}
#image_add table.dataintable_inquire td td{border:none;}
#image_add .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
.sortDragArea div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
table.imageAddInfo td .tabsContent{background-color:#efefef;}
.chosen-single span{padding-top:6px;}
.pageFormContent #lazada_attributes label{width:200px;}
ul.multi_select li {float:left;width:150px;}
.pageFormContent #lazada_attributes ul.multi_select li label {width:auto;float:none;display:inline;}
/* #image_add table{display:inline-block;} */
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
#image_add table.dataintable_inquire table.variationProductTable {
	min-width:100px;
	padding:0;
	margin:10px auto;
	align:center;
	border-width:0 0 0 1px;
	border-color:#888888;
	border-style:solid;
}
#image_add table.dataintable_inquire table.variationProductTable th, #image_add table.dataintable_inquire table.variationProductTable td {
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

.buttonActive{
	margin-left:10px;
	float:right;
}

-->
</style>
<script type="text/javascript">
//保存
function saveImage(){



    if($(".imageAddInfo .ztimgs .extra_checked:checked").length == 0) {
        alertMsg.error('至少选择一张主图');
        return false;
    }
    if($(".imageAddInfo .ftimgs .extra_checked:checked").length == 0){
        alertMsg.error('至少选择一张附图');
        return false;
    }

    var imageArray = [];

    $(".imageAddInfo .extra_checked:checked").each(function(){
        //var tmpArray = [];
       // tmpArray.push($(this).attr('key'));
        //tmpArray.push();
        imageArray.push($(this).val());
    });


	if (imageArray.length == 0){
		alertMsg.error('选中图片为空');
		return false;
	}else{
		$.bringBack({'skuinfo[<?php echo $publishParams['sku'];?>][image]':JSON.stringify(imageArray)});
	}
}

function closeImage(){
	$.pdialog.closeCurrent();
}

$(".imageAddInfo .ztimgs .extra_checked").live('click', function(event){
	var checked = !!$(this).attr("checked");
	if(checked){
		if($(".imageAddInfo .ztimgs .extra_checked:checked").length > 1){
			// alert('主图最大选择一张!');
			alertMsg.error('主图最大选择一张');
			return false;
			event.stopPropagation();
		}
	}
	event.stopPropagation();
});

$(".imageAddInfo .ftimgs .extra_checked").live('click', function(event){
	var unchecked = !$(this).attr("checked");
	if(unchecked){
		$(this).removeAttr("checked"); 
	}
	var checked = !!$(this).attr("extra_checked");
	if(checked){
		if($(".imageAddInfo .ftimgs .extra_checked:checked").length > 8){
			// alert('附图最大选择八张!');
			alertMsg.error('附图最大选择八张');
			return false;
			event.stopPropagation();
		}
	}	
	 event.stopPropagation();
});

$(".extra_checked").mousedown(function(event){
	 event.stopPropagation();
});
</script>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56" style="padding:0 5px;">
	    <div>
	       <div class="row imageAddInfo">
	       <?php
            $form = $this->beginWidget('ActiveForm', array(
					'id'                     => 'image_add',
					'enableAjaxValidation'   => false,  
					'enableClientValidation' => true,
					'clientOptions'          => array(
						'validateOnSubmit'       => true,
						'validateOnChange'       => true,
						'validateOnType'         => false,
						'afterValidate'          =>'js:afterValidate',
						'additionValidate'       =>'js:checkResult',
					),
					'action'                 => Yii::app()->createUrl('amazon/amazonproductadd/saveimage'), 
					'htmlOptions'            => array(        
                    	'class' => 'pageForm',         
                )
            ));
            ?> 
        		<table class="dataintable_inquire imageAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
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
				                </ul>
				                <input type="hidden" name="publish_sku" value="<?php echo $publishParams['sku'];?>" />

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
                                	<?php if (isset($skuImg['zt']) && $skuImg['zt']) { ?>
	                                	<div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
	                                    <?php foreach($skuImg['zt'] as $k=>$image):?>
	                                    <div style="position:relative;" class="aliexpress_image">
	                                        <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
	                                        <input type="checkbox" key="<?php echo $k;?>" <?php if($publishParams['publish_image_readonly'] == 1) echo 'disabled'; ?> class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[1][]" />
	                                    </div>
	                                    <?php endforeach;?>
	                             		</div>
                                    <?php } ?>

                                    <div style="clear:both;"></div>
                                </div>                               
                                
                                <div class="page unitBox ftimgs">
                                    <div><strong><?php echo Yii::t('amazon_product', 'Additional Images');?></strong></div>
                                    <div class="sortDrag sortDragShow" style="width:99%;margin:5px;min-height:100px">
                                        <?php if (!empty($skuImg)) { ?>
                                        <?php foreach($skuImg['ft'] as $k=>$image):?>
                                        <div style="position:relative;" class="aliexpress_image2">
                                            <img src="<?php echo $imgomsURL.$image;?>" style="width:80px;height:80px;" />
                                            <input type="checkbox" key="<?php echo $k;?>" <?php if($publishParams['publish_image_readonly'] == 1) echo 'disabled'; ?> class="extra_checked" value="<?php echo $k;?>" style="width: 30px;height: 30px;z-index: 100;position: absolute;left: 0px;top: 0px;" name="skuImage[2][]" />
                                        </div>
                                        <?php endforeach;?>
                                        <?php } ?>
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>
				            </td>
				        </tr>
                        <!-- 图片信息显示END -->		        				        
				    </tbody>
				</table>
				<div style="padding-top:10px;">
                    <ul> 
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent">
                                    <a class="close" onClick="closeImage();" href="javascript:void(0)"><?php echo Yii::t('system', 'Go Back');?></a>
                            </div>
                        </li>
                        <?php if($publishParams['publish_image_readonly'] != 1){ ?>                     
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent">
                                    <a class="confirm" onClick="saveImage();" href="javascript:void(0)"><?php echo Yii::t('system', 'Save');?></a>&nbsp;
                            </div>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">
$(function(){
	var selectedSubImageStr = $('input.image_<?php echo $productID;?>').val();
    var imageArray =[];

    if (selectedSubImageStr.length) {
        imageArray = JSON.parse(selectedSubImageStr);

    }

    var selectedSubImage = [];
    for(i=0; i< imageArray.length; i++) {
        //console.log(imageArray[i][1]);
        selectedSubImage.push(imageArray[i]);
    }

	//var selectedSubImage = selectedSubImageStr.split(",");
	if (selectedSubImage.length > 1){
		var selectedZtImage = selectedSubImage[0];	//主图
		selectedSubImage.splice(0,1);
		var selectedFtImage = selectedSubImage;		//附图
	}	
	if (selectedZtImage){
		$(".imageAddInfo .ztimgs .extra_checked").each(function(){
			if ($(this).val() == selectedZtImage){
				$(this).attr("checked",true);
			}
		});
	}
	if (selectedFtImage){		
		var sel_str = selectedFtImage.join(',');
		var i = 0;
		$(".imageAddInfo .ftimgs .extra_checked").each(function(){
			var num = 9000 + Number(i);
			$(this).parent().attr("rel",num);
			if ($.inArray($(this).val(), selectedFtImage) >= 0){
				$(this).attr("checked",true);
				var loc = sel_str.indexOf($(this).val());	//通过在字符串的位置定义顺序
				$(this).parent().attr("rel",loc);
			}else{
				i++;
			}
		});
		
		//重新按选中指定顺序排列（rel从小到大排），非选中的在后面自动排列
		var items = $(".imageAddInfo .ftimgs .aliexpress_image2").get();
        items.sort(
            function(a, b)
            {
				var elementone = Number($(a).attr("rel")); 
				var elementtwo = Number($(b).attr("rel"));
				if(elementone < elementtwo) return -1;   
				if(elementone > elementtwo) return 1;  
				return 0;
            }
        );

		var ul = $(".imageAddInfo .ftimgs .sortDrag"); 
		//通过遍历每一个数组元素，填充排序列表  
		$.each(items,function(i,li)		            
		{  
		    ul.append(li);  
		});
	}	
});
</script>