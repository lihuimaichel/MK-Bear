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
        'action' => Yii::app()->createUrl($this->route), 
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
				                <div style="float:left;margin-right:10px;">
                                                    <input type="radio" name="listing_type" id="listing_type_<?php echo LazadaProductAdd::LISTING_TYPE_VARIATION;?>" value="<?php echo LazadaProductAdd::LISTING_TYPE_VARIATION;?>" checked="checked" />
                                                    <label for="listing_type_<?php echo LazadaProductAdd::LISTING_TYPE_VARIATION;?>"><?php echo $listingType[LazadaProductAdd::LISTING_TYPE_VARIATION];?></label>
                                                </div>
				                <?php //endforeach;?>
				            </td>
				        </tr>
				        <tr>    
				            <td style="font-weight:bold;"><?php echo Yii::t('lazada', 'Listing Mode');?></td>
				            <td>
				                <?php foreach($listingMode as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
    				                <input type="radio" name="listing_mode" id="listing_mode_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo $value==LazadaProductAdd::LISTING_MODE_EASY ? 'checked="checked"' : '';?> />
    				                <label for="listing_mode_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>    
				            <td style="font-weight:bold;"><?php echo Yii::t('lazada', 'Listing Site');?></td>
				            <td>
				                <?php foreach($listingSite as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
    				                <input type="radio" name="listing_site" id="listing_site_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo $value==LazadaSite::SITE_MY ? 'checked="checked"' : '';?> onchange="showAccountList()" />
    				                <label for="listing_site_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('lazada', 'Product Images');?></td>
				            <td>
				                <div class="image_gallery">
    				                <?php foreach($skuImg['zt'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo str_replace(array('width=100', 'height=100'), array('width=800', 'height=800'), $img);?>" />
    				                </a>
    				                <?php endforeach;?>
				                </div>
				                <div>
				                    <p style="color:red;font-size:18px;">
				                        <?php echo Yii::t('lazada', 'Zt').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:void(0);"><?php echo count($skuImg['zt'])?></a>
				                        <?php echo ','.Yii::t('lazada', 'Ft').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:;"><?php echo count($skuImg['ft'])?></a>
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
                            <a rel="" id="product_add_next_btn" style="display:none;" target="navTab" href="lazada/lazadaproductadd/productaddstepthird">
                                <?php echo Yii::t('lazada', 'Fill Add Information')?>
                            </a> 
                            <input type="hidden" value="<?php echo $sku; ?>" name="sku"  />                     
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
	$('input[name="listing_type"]').change(function(){
		showAccountList();
	});

	//全选
	$('#allSelect').live('click',function(){
		var checkStatus = $(this).attr('checked');
		if(checkStatus=='checked'){
			$('ul.accounts').find('input[type="checkbox"]').attr('checked','checked');
		}else{
			$('ul.accounts').find('input[type="checkbox"]').removeAttr('checked');
		}
	});

	//下一步
	$('a#product_add_next_btn').click(function(){
		var data = $('form#product_add').serializeArray();
		var url = $(this).attr('href');
		$.each(data,function(i,item){
			url += '/'+item.name+'/'+item.value;
		});
		$(this).attr('href', url);
	});

	//显示账号列表
	function showAccountList(){
		var listing_type = $('input[name="listing_type"]').val();
		var siteID = $('input[name="listing_site"]:checked').val();
		$.ajax({
    			type:'post',
    			url:'lazada/lazadaproductadd/getableaccount',
    			data:{sku:'<?php echo $sku;?>', listing_type:listing_type, 'site_id': siteID},
				success:function(result){
					if(result.length > 0){
						var html= '<div><input type="checkbox" id="allSelect" /><label style="float:none;display:inline;" for="allSelect"><?php echo Yii::t('system', 'All Selected');?></label></div><br/>';
						html += '<ul class="accounts">';
						$.each(result,function(i,item){
							html += '<li style="width:180px;float:left">'
									+	'<input type="checkbox" name="accounts[]" value="'+item.id+'" />'
									+	'<a style="font-size:20px;text-decoration:underline;color:blue;" href="javascript:;">'+item.short_name+'</a>'
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

</script>