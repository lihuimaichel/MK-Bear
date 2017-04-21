<?php if(!$skuInfo) :?>
<script>
	$(function(){
		$.pdialog.open(
	    		'<?php echo Yii::app()->createUrl('wish/wishproductadd/index', array('dialog'=>1)) ?>', 
	    		'1', 
	    		'<?php echo Yii::t('lazada','Please input SKU');?>',
	    		{width:400, height:230, mask:true, fresh:true}
	    );
	});
</script>  
<?php 
exit();
endif;?>
<style>
<!--
.pageFormContent label{width:auto;}
-->
</style>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'wish_product_preadd',
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
				    	<?php /*if($listingType):?>
				    	<tr>
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('wish_listing', 'Listing Type');?>
				            	<br/><br/>
				            	<font style="color:red;">暂时无作用</font>
				            </td>
				            <td>
				                <?php foreach($listingType as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
				                    <?php if($skuInfo['product_is_multi']==Product::PRODUCT_MULTIPLE_MAIN && $value==WishProductAdd::LISTING_TYPE_VARIATION):?>
    				                <input type="radio" name="listing_type" id="listing_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php elseif($skuInfo['product_is_multi']!=Product::PRODUCT_MULTIPLE_MAIN && $value==WishProductAdd::LISTING_TYPE_FIXEDPRICE):?>
    				                <input type="radio" name="listing_type" id="listing_type_<?php echo $value;?>" value="<?php echo $value;?>" <?php echo 'checked="checked"';?> />
    				                <?php else:?>
    				                <input type="radio" name="listing_type" id="listing_type_<?php echo $value;?>" value="<?php echo $value;?>" checked="checked" />
    				                <?php endif;?>
    				                <label for="listing_type_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				  		<?php endif;*/?>

				        <tr>
				            <td style="font-weight:bold;" width="15%"><?php echo Yii::t('wish_listing', 'Product Images');?></td>
				            <td>
				                <div class="image_gallery">
				                	<?php if (!empty($skuImg['zt'])):?>
    				                <?php foreach($skuImg['zt'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo str_replace(array('width=100', 'height=100'), array('width=800', 'height=800'), $img);?>" />
    				                </a>
    				                <?php endforeach;?>
    				                <?php endif;?>
    				                <?php if (!empty($skuImg['ft'])):?>
    				                <?php /*foreach($skuImg['ft'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo $img;?>" />
    				                </a>
    				                <?php endforeach;?>
    				                <?php */endif;?>
				                </div>
				                <div>
				                    <p style="color:red;font-size:18px;">
				                        <?php echo Yii::t('lazada', 'Zt').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:void(0);"><?php echo !empty($skuImg['zt'])?count($skuImg['zt']):0;?></a>
				                        <?php echo ','.Yii::t('lazada', 'Ft').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:;"><?php echo !empty($skuImg['ft'])?count($skuImg['ft']):0;?></a>
				                    </p>
				                </div>
				                <?php echo $this->renderPartial('application.components.views._pic');?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;">
				                <?php echo Yii::t('wish_listing', 'Accounts');?><br/><br/>
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
                            <a rel="" id="wish_product_add_next_btn" style="display:none;" target="navTab" href="" _href="<?php echo Yii::app()->request->baseUrl;?>/wish/wishproductadd/addinfo">
                                <?php echo Yii::t('wish_listing', 'Fill Add Information')?>
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
		$('#wish_product_add_next_btn').attr('rel',page).show();
		showAccountList();
// 		$(document).keyup(function(e){
// 			if(e.keyCode==13){
// 				$('#wish_product_add_next_btn').click();
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
/*	$('#wish_product_preadd ul.accounts input[name="accounts[]"]').live('click', function(){
		//检测是否已经中一个了
		var accountChecked = $('#wish_product_preadd ul.accounts').find('input[type="checkbox"]:checked');
		if(accountChecked.length>1){
			alertMsg.error('只能选择一个账号进行发布！');
			return false;
		}
	});*/
	//下一步
	$('a#wish_product_add_next_btn').click(function(){
		var data = $('form#wish_product_preadd').serializeArray();
		var url = $(this).attr('_href');
		$.each(data,function(i,item){
			url += '/'+item.name+'/'+item.value;
		});
		$(this).attr('href', url);
	});

	//显示账号列表
	function showAccountList(){
		var listing_type = $('input[name="listing_type"]').val();
		$.ajax({
    			type:'post',
    			url:'<?php echo Yii::app()->createUrl("/wish/wishproductadd/getableaccount")?>', //采用url形式
    			data:{sku:'<?php echo $sku;?>', listing_type:listing_type, user_limit:1},
				success:function(result){
					if(result.length > 0){
						var html= '';
						html += '<ul class="accounts">';
						$.each(result,function(i,item){
                            var check_account_id = '<?php echo $account_id;?>';
                            var checkedVal = '';
                            if (item.id == check_account_id) {
                                checkedVal = 'checked';
                            }
							html += '<li style="width:180px;float:left">'
									+	'<input type="checkbox" name="accounts[]" value="'+item.id+'"'+checkedVal+' />'
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