<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent"> 
 
  
<div class="pageFormContent" layoutH="70">
<table class="dataintable_inquire" width="100%" cellspacing="1" cellpadding="3" border="0">
    <tbody>
    	<tr>
        	<td>输入SKU</td>
        	<td>
        		<input type="text" name="sku" style="width:180px;height:28px;line-height:28px;"/>
        	</td>
        </tr>
    	<tr>
            <td width="25%">
            	加密SKU
            </td>
            <td>
				<input type="text" name="encrypetSKU" disabled style="width:180px;height:28px;line-height:28px;"/>
            </td>
        </tr>
    </tbody>
</table>

</div>
    <div class="formBar">
        <ul> 
        	 <li>
        		<div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit" id="wish_listing_encrypt_sku"><?php echo Yii::t('wish_listing', 'Encry SKU')?></button>                       
                    </div>
                </div>
       	    </li>             	
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
</div>
<script type="text/javascript">
$(function(){
	$("#wish_listing_encrypt_sku").click(function(){
		var sku = $(".dataintable_inquire").find("input[name='sku']").val();
		var param = {'sku':sku};
		var url = '<?php echo Yii::app()->createUrl("wish/wishlisting/createencrysku");?>';
		$.post(url, param, function(data){
			if(data.statusCode == 200){
				$(".dataintable_inquire").find("input[name='encrypetSKU']").val(data.message);
			}else{
				alertMsg.error(data.message);
			}
		}, 'json');
	});
});
</script>