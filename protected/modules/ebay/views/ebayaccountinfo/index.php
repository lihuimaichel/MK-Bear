<style>
<!--
/* #ebay_product_add .pageFormContent label{width:auto;} */
#ebay_account_info_list table td li{line-height:20px;}
#ebay_account_info_list table td font.bold{font-weight:bold;}
/*#ebay_account_info_list table.dataintable_inquire td td{border:none;}*/
#ebay_account_info_list .sortDragShow div{border:1px solid #B8D0D6;padding:5px;margin:5px;width:80px;height:80px;display:inline-block;cursor:move;}
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
                'id' => 'ebay_account_info_list',
                'enableAjaxValidation' => false,  
                'enableClientValidation' => true,
                'clientOptions' => array(
                    'validateOnSubmit' => true,
                    'validateOnChange' => true,
                    'validateOnType' => false,
                    'afterValidate'=>'js:afterValidate',
                	'additionValidate'=>'js:checkResult',
                ),
                'action' => Yii::app()->createUrl('ebay/ebayaccountinfo/savedata'), 
                'htmlOptions' => array(        
                    'class' => 'pageForm',         
                )
            ));
            ?> 
            	<div class="formBar">
                    <ul> 
                    	<li style="line-height: 30px;">
                        	<span>注意，只保存和更新第一个选框选中的项</span>
                        </li>
                        <li style="width: 60px">
                            <div class="buttonActive">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="updateInfo();" href="javascript:void(0)"><?php echo Yii::t('system', 'Refresh');?></a>
                                </div>
                            </div>
                        </li>
                        <li style="width: 60px">
                            <div class="buttonActive">
                                <div class="buttonContent">  
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('system', 'Save');?></a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('ebay/ebayaccountinfo/index');?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('system', 'Save');?></a>
                    </ul>
                </div>
        		<table class="dataintable_inquire productAddInfo" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				    	<tr>
				            <th width="10%" style="font-weight:bold;"><input type="checkbox" id="allSelect" />全选</th>
				            <th width="10%" style="font-weight:bold;">自动更改数量</th>
				            <th width="10%" style="font-weight:bold;">账号名称</th>
				            <th width="10%" style="font-weight:bold;">总数量</th>
				            <th width="10%" style="font-weight:bold;">总金额</th>
				            <th width="10%" style="font-weight:bold;">空余比例</th>
				            <th width="10%" style="font-weight:bold;">最低可变数</th>
				            <th width="10%" style="font-weight:bold;">可用数量（比例）</th>
				            <th width="10%" style="font-weight:bold;">可用金额（比例）</th>
				            <th width="10%" style="font-weight:bold;">更新时间</th>
				        </tr>
				        <?php if($accountList):?>
				        <?php foreach ($accountList as $account):?>
                        <tr>
                        	<td>
                        		<input type="checkbox" name="accounts[]" value="<?php echo $account['account_id'];?>"/>
                        	</td>
                        	<td>
                        		<input type="checkbox" name="listdata[<?php echo $account['account_id'];?>][auto_qty]" <?php if($account['auto_qty']){echo "checked";}?> value="<?php echo $account['auto_qty'];?>"/>
                        	</td>
				            <td style="font-weight:bold;"><?php echo $account['short_name'];?></td>
				            <td>
				            	<input type="text" name="listdata[<?php echo $account['account_id'];?>][quantity_limit]" value="<?php echo $account['quantity_limit'];?>"/>
				            </td>
				            <td>
				            	<input type="text" name="listdata[<?php echo $account['account_id'];?>][amount_limit]" value="<?php echo $account['amount_limit'];?>"/>
				            </td>
				            
				            <td style="text-align: center; vertical-align: middle; line-height: 30px;">
				            	<input type="text" name="listdata[<?php echo $account['account_id'];?>][remain_rate]" style="width:60px;" value="<?php echo $account['remain_rate'];?>"/>%
				            </td>
				            <td>
				            	<input type="text" name="listdata[<?php echo $account['account_id'];?>][lowest_qty]" value="<?php echo $account['lowest_qty'];?>"/>
				            </td>
				            <td>
				            	<?php echo $account['quantity_limit_remaining'];?>(<?php if($account['quantity_limit']>0) echo round($account['quantity_limit_remaining']/$account['quantity_limit']*100, 2); else echo 0;?>%)
				            </td>
				            <td>
				            	<?php echo $account['amount_limit_remaining'];?>(<?php if($account['amount_limit']>0) echo round($account['amount_limit_remaining']/$account['amount_limit']*100, 2); else echo 0;?>%)
				            </td>
				            <td>
				            	<?php echo $account['update_time'];?>
				            </td>
				        </tr>
				        <?php endforeach;?>
				        <?php endif;?>
				    </tbody>
				</table>
				<div class="formBar">
                    <ul> 
                        <li style="line-height: 30px;">
                        	<span>注意，只保存和更新第一个选框选中的项</span>
                        </li>
                        <li style="width: 60px">
                            <div class="buttonActive">
                                <div class="buttonContent">
                                    <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)"><?php echo Yii::t('system', 'Save');?></a>
                                </div>
                            </div>
                        </li>
                        <a href="<?php echo Yii::app()->createUrl('ebay/ebayaccountinfo/index');?>" target="navTab" style="display:none;" class="display_list"><?php echo Yii::t('system', 'Save');?></a>
                    </ul>
                </div>
            	<?php $this->endWidget(); ?>
        	</div>
        	
        	<div style="clear:both;"></div>
	    </div>
    </div>
</div>
<script type="text/javascript">

$("#allSelect").click(function(){
	$("input[name='accounts[]']").attr("checked", !!$(this).attr("checked"));
});
function saveInfo(){
	if($("input[name='accounts[]']:checked").length<=0){
		alertMsg.error('还没有选择一项');
		return;
	}
	$.ajax({
			type: 'post',
			url: $('form#ebay_account_info_list').attr('action'),
			data:$('form#ebay_account_info_list').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
					setTimeout("navTab.reload();", 2000);
				}
			},
			dataType:'json'
	});
}

function updateInfo(){
	if($("input[name='accounts[]']:checked").length<=0){
		alertMsg.error('还没有选择一项');
		return;
	}
	$.ajax({
			type: 'post',
			url: '<?php echo Yii::app()->createUrl("ebay/ebayaccountinfo/updatelimitremaining");?>',
			data:$('form#ebay_account_info_list').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
					setTimeout("navTab.reload();", 2000);
				}
			},
			dataType:'json'
	});
}
</script>