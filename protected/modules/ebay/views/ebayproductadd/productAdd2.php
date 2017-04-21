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
				            <td width="15%" style="font-weight:bold;"><?php echo Yii::t('ebay', 'Listing Type');?></td>
				            <td>
				                <?php foreach($listingType as $value=>$type):?>
				                <div style="float:left;margin-right:10px;">
    				                <input type="radio" name="listing_type" id="listing_type_<?php echo $value;?>"  value="<?php echo $value;?>" <?php echo $value==$currenListingType ? 'checked' : '';?>/>
    				                <label for="listing_type_<?php echo $value;?>"><?php echo $type;?></label>
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>

				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('ebay', 'Product Images');?></td>
				            <td>
				            	<?php if(!empty($skuImg['zt'])):?>
				                <div class="image_gallery">
    				                <?php foreach($skuImg['zt'] as $k=>$img): ?>
    				                <a onmouseover="showPreview(event);" onmouseout="hidePreview(event);" href="javascript:;" title="<?php echo $k;?>" style="margin-right:10px;">
    				                    <img src="<?php echo $img;?>" width=80 height=80 large-src="<?php echo str_replace(array('width=100', 'height=100'), array('width=800', 'height=800'), $img);?>" />
    				                </a>
    				                <?php break; endforeach;?>
				                </div>
				                <?php endif;?>
				                <div>
				                    <p style="color:red;font-size:18px;">
				                        <?php echo Yii::t('ebay', 'Zt').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:void(0);"><?php echo isset($skuImg['zt']) ? count($skuImg['zt']):0;?></a>
				                        <?php echo ','.Yii::t('ebay', 'Ft').': ';?>
				                        <a style="color:blue;font-size:18px; href="javascript:;"><?php echo isset($skuImg['ft']) ? count($skuImg['ft']) : 0;?></a>
				                    </p>
				                </div>
				                <?php echo $this->renderPartial('application.components.views._pic');?>

				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;"><?php echo Yii::t('ebay', 'Site');?></td>
				            <td>
				                <?php foreach($siteArr as $id=>$site):?>
				                <div style="display: inline-block">
				                <label for="listing_site_<?php echo $id;?>" style="float: right;"><?php echo $site;?></label>
				                <input type="radio" name="listing_site" id="listing_site_<?php echo $id;?>" <?php if ($id == $site_id){?> checked <?php }?> value="<?php echo $id;?>" />
				                </div>
				                <?php endforeach;?>
				            </td>
				        </tr>
				        <tr>
				            <td style="font-weight:bold;">
				                <?php echo Yii::t('ebay', 'Accounts');?><br/><br/>
				                <font style="color:red;"><?php echo Yii::t('ebay', 'Only Show The Accounts Which Can Be Published');?></font>
				            </td>
				            <td class="accoutshow">
				                <?php echo Yii::t('ebay', 'Please Choose Site');?>
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
                            <a rel="" id="product_add_next_btn" style="display:none;" target="navTab" href="ebay/ebayproductadd/productaddstepthird">
                                <?php echo Yii::t('ebay', 'Fill Add Information')?>
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
        loadAccount();
    });
    //加载账号列表
	$('input[name="listing_site"],input[name="listing_type"]').change(function(){
        loadAccount();
	});

	//全选
	$('#allSelect').live('click',function(){
		var checkStatus = $(this).attr('checked');
		if(checkStatus=='checked'){
			$('ul.accounts').find('input[type="checkbox"]').each(function(){
				if(typeof $(this).attr("disabled") == 'undefined'){
					$(this).attr('checked', 'checked');
				}

			});
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

	//获取账号
	function loadAccount()
    {
        var listing_type = $('input[name="listing_type"]:checked').val();
        var listing_site = $('input[name="listing_site"]:checked').val();
        $.ajax({
            type:'post',
            url:'ebay/ebayproductadd/getableaccount',
            data:{sku:'<?php echo $sku;?>', listing_type:listing_type, listing_site:listing_site },
            success:function(result){
                if(result.length > 0){
                    var html= '<div><input type="checkbox" id="allSelect" /><label style="float:none;display:inline;" for="allSelect"><?php echo Yii::t('system', 'All Selected');?></label></div><br/>';
                    html += '<ul class="accounts">';
                    $.each(result,function(i,item){
                        var disabledH = "", red = "";
                        if(item.flag){
                            disabledH = "disabled";
                            red = "color:#cccccc;";
                        }
                        var check_account_id = '<?php echo $account_id;?>';
                        var checkedVal = '';
                        if (item.id == check_account_id) {
                            checkedVal = 'checked';
                        }
                        html += '<li style="width:100px;float:left;">'
                            +	'<input type="checkbox" name="accounts[]" value="'+item.id+'" '+ checkedVal + disabledH + '/>'
                            +	'<a style="font-size:20px;text-decoration:underline;color:blue;'+red+'" href="javascript:;">'+item.short_name+'</a>'
                            +'</li>';
                    });
                    html += '</ul>';
                }else{
                    var html = 'No Result!';
                }
                $('#listing_site_'+listing_site).attr("checked", true);
                $('.accoutshow').html(html);
            },
            dataType:'json'
        });
    }

</script>