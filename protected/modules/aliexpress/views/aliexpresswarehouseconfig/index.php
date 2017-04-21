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

table.dataintable_inquire td {
    background-color:white;
}
-->
</style>
<div class="pageContent">
    <div class="pageFormContent" layoutH="56">
	    <div class="dot7" style="padding:5px;">
	       <div class="row productAddInfo" style="width:90%;float:left;">
	       <?php
            $form = $this->beginWidget('ActiveForm', array(
                'id' => 'aliexpress_warehouse_config_add',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('aliexpress/aliexpresswarehouseconfig/savedata'), 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
            	<div class="formBar">
                    <ul> 
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('system', 'Save');?></a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('aliexpress/aliexpresswarehouseconfig/index');?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('system', 'Save');?></a>
                    </ul>
                </div>
        		<table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				    	
				        <?php if($warehouseList):?>
				        
                        <tr>
				            <td width="15%" style="font-weight:bold;">仓库列表</td>
				            <td>
				            	<?php foreach ($warehouseList as $warehouse):?>
				                <div style="float:right; display:inline; width:150px;">
                             		<?php echo CHtml::label("{$warehouse['name']}({$warehouse['id']})", $warehouse['id'], array('style'=>'float:left')); ?>
                             		<?php echo CHtml::checkBox("warehouse_config[]", $warehouse['flag'], array('value'=>$warehouse['id'],	'id'=>$warehouse['id'])); ?>
                            	</div>
                            	<?php endforeach;?>
				            </td>
				        </tr>
				        <?php endif;?>
				    </tbody>
				</table>
				<div class="formBar">
                    <ul> 
                        <li>
                            <div class="buttonActive">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('system', 'Save');?></a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('aliexpress/aliexpresswarehouseconfig/index');?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('system', 'Save');?></a>
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">
function saveInfo(){
	$.ajax({
			type: 'post',
			url: $('form#aliexpress_warehouse_config_add').attr('action'),
			data:$('form#aliexpress_warehouse_config_add').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct('更新成功！');
					navTab.reload();
				}
			},
			dataType:'json'
	});
}
</script>