<style>
<!--
.pageFormContent label{width:auto;}
-->
</style>
<div class="pageContent"> 
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
        'action' => Yii::app()->createUrl('/aliexpress/aliexpressproduct/step3'), 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56">
	    <div class="bg14 pdtb2 dot">
	         <strong>[
            	<?php echo 
              		'SKU:'.CHtml::link($sku, '/products/product/viewskuattribute/sku/'.$sku, 
			array('style'=>'color:blue;','target'=>'dialog','width'=>'1100','height'=>'600','mask'=>true, 'rel' => 'external', 'external' => 'true'))
			?>
            ]</strong>
	    </div>
	    <div class="dot7" style="padding:5px;">
        	<div class="row">
        		<table class="dataintable_inquire" width="100%" cellspacing="1" cellpadding="3" border="0">
				    <tbody>
				    	<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('lazada', 'Listing Type');?></td>
				            <td>
				                <?php foreach($publishTypeList as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
				                    <?php if($skuInfo['product_is_multi']==Product::PRODUCT_MULTIPLE_MAIN && $value==AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION):?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php elseif($skuInfo['product_is_multi']!=Product::PRODUCT_MULTIPLE_MAIN && $value==AliexpressProductAdd::PRODUCT_PUBLISH_TYPE_FIXEDPRICE):?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php else:?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" />
    				                <?php endif;?>
    				                <label for="publish_type_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>    
				            <td style="font-weight:bold;width:10%"><?php echo Yii::t('aliexpress_product', 'Listing Mode');?></td>
				            <td>
				                <?php foreach($publishModeList as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
    				                <input type="radio" name="publish_mode" id="publish_mode_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo $value==AliexpressProductAdd::PRODUCT_PUBLISH_MODE_EASY ? 'checked="checked"' : '';?> />
    				                <label for="publish_mode_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Images');?></td>
				            <td>
				                <div class="image_gallery">
				                	<?php if (!empty($skuImg)) { ?>
    				                <?php foreach($skuImg['zt'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo str_replace(array('width=100', 'height=100'), array('width=800', 'height=800'), $img);?>" />
    				                </a>
    				                <?php endforeach;?>
    				                <?php } ?>
				                </div>
				                <div>
				                    <p style="color:red;font-size:18px;">
				                        <?php echo Yii::t('lazada', 'Zt').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:void(0);"><?php echo !empty($skuImg) ? count($skuImg['zt']) : 0;?></a>
				                        <?php echo ','.Yii::t('lazada', 'Ft').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:;"><?php echo !empty($skuImg) ? (isset($skuImg['ft']) ? count($skuImg['ft']) : 0) : 0;?></a>
				                    </p>
				                </div>
				                <?php echo $this->renderPartial('application.components.views._pic');?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;">
				                <?php echo Yii::t('lazada', 'Accounts');?><br/><br/>
				                <font style="color:red;"><?php echo Yii::t('lazada', 'Only Show The Accounts Which Can Be Published');?></font>
				            </td>
				            <td class="accoutshow">
				                
				            </td>
				        </tr>
				    </tbody>
				</table>
        	</div>
	    </div>
	    <div class="formBar">
            <ul> 
                <li>
                    <div class="buttonActive">
                        <div class="buttonContent">  
                            <a rel="" id="product_add_next_btn" onclick="nextButton(this)" style="display:none;" href="javascript:void(0);">
                                <?php echo Yii::t('lazada', 'Fill Add Information')?>
                            </a> 
                            <input type="hidden" value="<?php echo $sku; ?>" name="publish_sku"  />                 
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script>
    //显示下一步的按钮
    $(function(){
        var page = $('#navTab ul.navTab-tab li.selected').attr('tabid');
		$('#product_add_next_btn').attr('rel',page).show();
		showAccountList();
// 		$(document).keyup(function(e){
// 			if(e.keyCode==13){
// 				$('#product_add_next_btn').click();
// 			}
// 		});
    });
    //加载账号列表
	$('input[name="publish_type"]').change(function(){
		showAccountList();
	});

	//全选
	$('#allSelect').live('click',function(){
		var checkStatus = $(this).attr('checked');
		if(checkStatus=='checked'){
			$('ul.accounts').find('input[type="checkbox"]').not(":disabled").attr('checked','checked');
		}else{
			$('ul.accounts').find('input[type="checkbox"]').removeAttr('checked');
		}
	});

	//下一步
/* 	$('a#product_add_next_btn').click(function(){
		var data = $('form#product_add').serializeArray();
		var url = $(this).attr('href');
		$.each(data,function(i,item){
			url += '/'+item.name+'/'+item.value;
		});
		$(this).attr('href', url);
	}); */

	//显示账号列表
	function showAccountList(){
		var publish_type = $('input[name="publish_type"]').val();
		var account_id = '<?php echo $account_id; ?>';
		$.ajax({
    			type:'post',
    			url:'aliexpress/aliexpressproductadd/getableaccount',
    			data:{publish_sku:'<?php echo $sku;?>'},
				success:function(result){
					if(result.length > 0){
						var html= '<div><input type="checkbox" id="allSelect" /><label style="float:none;display:inline;" for="allSelect"><?php echo Yii::t('system', 'All Selected');?></label></div><br/>';
						html += '<ul class="accounts">';
						$.each(result,function(i,item){
							var disabledH = "", red = "", onclick = '';
                            var checkedVal = '';

							if(item.flag){
								disabledH = "disabled";
								red = "color:#cccccc;";
								onclick = "";
							}else{
								disabledH = "";
								red = "";
								onclick = 'onclick="chooseAccount(' + item.id + ')"';
                                if (item.id == account_id) {
                                    checkedVal = 'checked';
                                }
							}
							var is_overseas_warehouse_notice = item.is_overseas_warehouse == 'true' ? '(海外仓)' : '';
							html += '<li style="width:180px;float:left">'
									+	'<input type="checkbox" name="accounts[]" is_overseas_warehouse="'+item.is_overseas_warehouse+'" value="'+item.id+'" '+ checkedVal +disabledH+' />'
									+	'<a style="font-size:20px;text-decoration:underline;color:blue;'+red+'" href="javascript:;" '+onclick+'>'+item.short_name + is_overseas_warehouse_notice +'</a>'
									+'</li>';
						});
						html += '</ul>';
					}else{
						var html = 'No Result!';
					}
					$('.accoutshow').html(html);
				},
				dataType:'json'
		});
	}

	function nextButton(obj, event) {
		var allCheckNum = 0;
		var allOverseasWarehouseNum = 0;
		$("input[name^='accounts']").each(function(){
			if ($(this).is(":checked")){
				allCheckNum++;
				if ($(this).attr('is_overseas_warehouse') == 'true') allOverseasWarehouseNum++;	
			}
		})
		if (allCheckNum != allOverseasWarehouseNum && allOverseasWarehouseNum != 0){
			alertMsg.error('多个账号一起刊登只能选都不是海外仓用户或则都是海外仓用户，不可以混选！');
			return false;
		} 
		var $this = $(obj);
		var title = $this.attr("title") || $this.text();
		var tabid = $this.attr("rel") || "_blank";
		var url = 'aliexpress/aliexpressproductadd/step3';
		var title = $this.attr("title") || $this.text();
		var formData = $('form#product_add').serialize();
		navTab.openTab(tabid, url, {data: formData, title: title, fresh: true});
		//navTab.reload(url, {data:formData, navTabId:tabid, title:title});
		return false;
	}

	function chooseAccount(id) {
		var $this = $('#product_add_next_btn');
		var title = $this.attr("title") || $this.text();
		var tabid = $this.attr("rel") || "_blank";
		var url = 'aliexpress/aliexpressproductadd/step3';
		var title = $this.attr("title") || $this.text();
		var formData = $('form#product_add').serialize();
		formData += '&accounts[]=' + id;
		navTab.openTab(tabid, url, {data: formData, title: title, fresh: false});
		//navTab.reload(url, {data:formData, navTabId:tabid, title:title});
		return false;
	}
</script>