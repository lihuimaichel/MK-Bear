<style>
.pageFormContent #domestic label, .pageFormContent #international label{
	width:auto;
}

.pageFormContent #domestic select,.pageFormContent #international select {
    float: none;
}

</style>
<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    ///*
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'getorderid-grid',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl("/aliexpress/aliexpressnotautomatic/doaction"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    //*/
    ?>
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:auto;" id="formMainJS">
            <div class="row">
        		使用说明：  要拉取的账户是必选的，如果只选择了账户，没填写产品ID，则会全部拉取这个账户下的所有产品。
        		                      如果选择账户后，填写了这个账户下的产品ID，则会只拉取这一个产品信息，有多个产品可以全部填写一起拉取   		
        	</div>        
        	<div class="row">
        		<label for="EbayProductAttributeTemplate_site_id">账号</label>        		
        		<select name="account_id" id="EbayProductAttributeTemplate_site_id" class="userIdClass">
					<option value="">   请选择    </option>
					<?php foreach (AliexpressAccount::getIdNamePairs() as $key=>$val):?>
					<option value="<?php echo $key;?>">   <?php echo $val;?>    </option>
					<?php endforeach;?>
					
				</select> 
				&nbsp;&nbsp;&nbsp; 
				<input 
					type="button" 
					value="  增加产品ID  " 
					onClick="$('#formMainJS').append($('.tempHtml').html());"
				/>
 			</div>
			
	        
	        <div class="row">
        		<label for="EbayProductAttributeTemplate_return_description">产品ID</label>        		
        		<input type="text" class="textInput aliexpress_product_id_class" name="aliexpress_product_id" />
        		<span style="cursor: hand;"></span>     		
        	</div>
				
			
        </div>
        <br/>
		<div class="pd5" style="height:auto;">
			<div class="row" style="margin-left:400px">
	                <div class="buttonActive">
                          <div class="buttonContent">  
                               <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)">&nbsp;&nbsp;拉取&nbsp;&nbsp;</a>
                          </div>
                   </div>
                   
                   <a href="<?php echo Yii::app()->createUrl('/aliexpress/aliexpressorder/getorderfilterids');?>" target="navTab" style="display:none;" class="display_list">速卖通订单拉取</a>
	          </div>
                        
        </div>

    </div>

    <?php $this->endWidget(); ?>
</div>

<div class="tempHtml" style="display:none;">
	        <div class="row">
        		<label for="EbayProductAttributeTemplate_return_description">产品ID</label>        		
        		<input type="text" class="textInput aliexpress_product_id_class" name="aliexpress_product_id" />
        		<span onclick="$(this).parent().remove();"  style="cursor: pointer;">&nbsp;&nbsp;&nbsp;删除</span>     		
        	</div>	
</div>

<script type="text/javascript">
//保存刊登数据
function saveInfo(){
	var userID = $('.userIdClass').val();
	var productList = '';
	//alert(userID);
	if ($.trim(userID) == ""){
		userID = 0;
		alert('请选择账号！');
		return false;
	}

	$('.aliexpress_product_id_class').each(function(){
		if ($(this).parent().parent().attr('id') == 'formMainJS'){			
			//alert($(this).val());			
			var productID = $.trim($(this).val());
			if ($.trim(productID) != ""){
				productList = productList + productID + ',';
			}				
		}		
	});

	$.ajax({
		type: 'post',
		url: '/aliexpress/aliexpressnotautomatic/doactionajax',
		data:{account_id:userID,product_id_list:productList},
		success:function(result){
			//alert(result);
			console.log(result);
			if (result != null && typeof result == 'object'){
				if (result.status == 'failure'){
					alert(result.msg);
				} else if (result.status == 'success'){
					alert('拉取成功！');
				}
			}
		},
		
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			 //alert(XMLHttpRequest.status);
			console.log(XMLHttpRequest.status);
			 if (XMLHttpRequest.status == 500){
				 setTimeout(function(){
					 //$('.saveBtn').click();
				 },288);
			 }
		},
		
		dataType:'json'
    });
	/* 	
	$('.aliexpress_product_id_class').each(function(){
		if ($(this).parent().parent().attr('id') == 'formMainJS'){			
			//alert($(this).val());			
			var productID = $.trim($(this).val());
			if ($.trim(productID) == ""){
				productID = 0;
			}
			if (productID == 0 && userID == 0){
				alert('请选择账号或者填写产品ID！');
				return false;				
			}
			$.ajax({
					type: 'post',
					url: '/aliexpress/aliexpressproduct/getproductlist' + '/account_id/'+ userID +'/status//productId/'+ productID +'/offLineTime/0/minute/0/offset/0',
					data:{},
					success:function(result){
						console.log(result);
					},
					dataType:'json'
			});			
		}
		
	});
	
	
	$.ajax({
			type: 'post',
			url: $('form#getorderid-grid a.display_list').attr('href'),
			data:$('form#getorderid-grid').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
					//setTimeout("navTab.reload()", 1500);
				}
			},
			dataType:'json'
	});
	*/
}
</script>