<style>
<!--
.pageFormContent label{width:auto;}
-->
</style>
<div class="pageContent"> 
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
        'action' => Yii::app()->createUrl('/amazon/amazonproductadd/step3'), 
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
				    	<!--
				    	<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('amazon_product', 'Listing Type');?></td>				            
				            <td>
							
								//暂时关闭多属性选项
				                <?php foreach($publishTypeList as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
				                    <?php if($skuInfo['product_is_multi']==Product::PRODUCT_MULTIPLE_MAIN && $value==AmazonProductAdd::PRODUCT_PUBLISH_TYPE_VARIATION):?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php elseif($skuInfo['product_is_multi']!=Product::PRODUCT_MULTIPLE_MAIN && $value==AmazonProductAdd::PRODUCT_PUBLISH_TYPE_SINGLE):?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php else:?>
    				                <input type="radio" name="publish_type" id="publish_type_<?php echo $value;?>" value="<?php echo $value;?>" />
    				                <?php endif;?>
    				                <label for="publish_type_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				             
				                <div style="float:left;margin-right:10px;">
				                	<input type="radio" name="publish_type" id="publish_type_1" value="1" checked="checked"><label for="publish_type_1">单品刊登</label>
				                </div>				               
				            </td>				            
				        </tr>
				        -->
				        <tr>    
				            <td style="font-weight:bold;width:10%"><?php echo Yii::t('amazon_product', 'Listing Mode');?></td>
				            <td>
				                <?php foreach($publishModeList as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
    				                <input type="radio" name="publish_mode" id="publish_mode_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo $value==AmazonProductAdd::PRODUCT_PUBLISH_MODE_EASY ? 'checked="checked"' : '';?> />
    				                <label for="publish_mode_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('amazon_product', 'Main Images');?></td>
				            <td>
				                <div class="image_gallery">
				                	<?php if (isset($skuImg['zt']) && $skuImg['zt']) { ?>
    				                <?php foreach($skuImg['zt'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo str_replace(array('width=100', 'height=100'), array('width=800', 'height=800'), $img);?>" />
    				                </a>
    				                <?php endforeach;?>
    				                <?php } ?>
				                </div>
				                <div>
				                    <p style="color:red;font-size:18px;">
				                        <?php echo Yii::t('amazon_product', 'Zt').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:void(0);"><?php echo (isset($skuImg['zt']) && $skuImg['zt']) ? count($skuImg['zt']) : 0;?></a>
				                        <?php echo ','.Yii::t('amazon_product', 'Ft').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:;"><?php echo (isset($skuImg['ft']) && $skuImg['ft']) ? count($skuImg['ft']) : 0;?></a>
				                    </p>
				                </div>
				                <?php echo $this->renderPartial('application.components.views._pic');?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('amazon_product', 'Site');?></td>
				            <td>				            	
				                <?php foreach($siteArr as $k => $site):?>
				                	<div style="float:left;margin-right:20px;">
						                <div style="float:left;padding-top:2px;"><input type="radio" name="publish_site" id="publish_site_<?php echo $k;?>" <?php if($k == 1) echo 'checked';?> value="<?php echo $site['CountryCode'];?>" /></div>
						                <div style="float:left;"><label for="publish_site_<?php echo $k;?>"><?php echo $site['CountryName'];?>(<?php echo strtoupper($site['CountryCode']);?>)</label></div>
				                	</div>
				                <?php endforeach;?>				                
				            </td>
				        </tr>				        
				        <tr>
				            <td style="font-weight:bold;">
				                <?php echo Yii::t('amazon_product', 'Account Id');?><br/><br/>
				                <font style="color:gray;">（<?php echo Yii::t('amazon_product', 'Only Show The Accounts Which Can Be Published');?>）</font>
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
                                <?php echo Yii::t('amazon_product', 'Fill Add Information')?>
                            </a> 
                            <input type="hidden" value="<?php echo $sku; ?>" name="publish_sku" />  
                            <input type="hidden" value="1" name="publish_type" />                                           
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
	$('input[name="publish_site"],input[name="publish_type"]').change(function(){
		showAccountList();
	});

	//全选
	$('#allSelect').live('click',function(){
		var checkStatus = $(this).attr('checked');
		if(checkStatus=='checked'){
			$('ul.accounts').find('input[type="checkbox"]').not(':disabled').attr('checked','checked');
		}else{
			$('ul.accounts').find('input[type="checkbox"]').removeAttr('checked');
		}
	});

	//只允许选中一个账号
	$('ul.accounts input[name="accounts[]"]').live('click', function(){		
		var accountChecked = $('ul.accounts').find('input[type="checkbox"]:checked');
		if(accountChecked.length>1){
			alertMsg.error('只能选择一个账号进行刊登！');
			return false;
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
		var publish_type = $('input[name="publish_type"]:checked').val();
		var publish_site = $('input[name="publish_site"]:checked').val();
		$.ajax({
    			type:'post',
    			url:'amazon/amazonproductadd/getableaccount',
    			data:{publish_sku:'<?php echo $sku;?>',publish_site:publish_site},
				success:function(result){
					if(result.length > 0){
						var html= '<div><input type="checkbox" id="allSelect" /><label style="float:none;display:inline;" for="allSelect"><?php echo Yii::t('system', 'All Selected');?></label></div><br/>';
						html += '<ul class="accounts">';
						$.each(result,function(i,item){
							var disabledH = "", red = "", onclick = '';
							if(item.flag){
								disabledH = "disabled";
								red       = "color:#cccccc;";
								onclick   = "";
							}else{
								disabledH = "";
								red       = "";
								onclick   = 'onclick="chooseAccount(' + item.id + ')"'
							}
                            var check_account_id = '<?php echo $account_id;?>';
                            var checkedVal = '';
                            if (item.id == check_account_id) {
                                checkedVal = 'checked';
                            }
							html += '<li style="width:180px;height:25px;float:left;">'
									+	'<input type="checkbox" name="accounts[]" value="'+item.id+'" '+checkedVal+disabledH+'/>'
									+	'<a style="font-size:20px;text-decoration:underline;color:blue;'+red+'" href="javascript:;" '+onclick+'>'+item.short_name+'</a>'
									+'</li>';
						});
						html += '</ul>';
					}else{
						var html = '<div style="padding:5px 2px;">没有相关记录！</div>';
					}
					$('.accoutshow').html(html);
				},
				dataType:'json'
		});
	}

	function nextButton(obj, event) {
		var $this    = $(obj);
		var title    = $this.attr("title") || $this.text();
		var tabid    = $this.attr("rel") || "_blank";
		var url      = 'amazon/amazonproductadd/step3';
		var formData = $('form#product_add').serialize();
		navTab.openTab(tabid, url, {data: formData, title: title, fresh: false});
		//navTab.reload(url, {data:formData, navTabId:tabid, title:title});
		return false;
	}

	function chooseAccount(id) {
		var $this    = $('#product_add_next_btn');
		var title    = $this.attr("title") || $this.text();
		var tabid    = $this.attr("rel") || "_blank";
		var url      = 'amazon/amazonproductadd/step3';
		var formData = $('form#product_add').serialize();
		formData += '&accounts[]=' + id;
		navTab.openTab(tabid, url, {data: formData, title: title, fresh: false});
		//navTab.reload(url, {data:formData, navTabId:tabid, title:title});
		return false;
	}
</script>