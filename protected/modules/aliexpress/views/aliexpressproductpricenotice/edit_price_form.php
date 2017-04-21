<style type="text/css">
    label.alt{width: 128px; opacity: 1;height:20px;margin:0;line-height:22px;}
    .items
    {
        font-family:"Trebuchet MS", Arial, Helvetica, sans-serif;
        width:100%;
        border-collapse:collapse;
    }
    .items td, .items th
    {
        font-size:1em;
        border:1px solid #d3d3d3;
        padding:2px 2px 2px 0px;
    }
    .items th
    {
        font-size:12px;
        font-weight:bold;
        text-align:left;
        padding-bottom:4px;
        background-color:#888;
        color:#fff;
    }
    .items tr.alt td
    {
        color:#000000;
        background-color:#98bf21;
    }
</style>

<form action="#" method="post" name="batchChangePrice" class="batchChangePriceClass" >
<div class="pageContent">
    <div class="tabs">
	 	<div class="tabsContent" style="height:100%;">
 			<div class="pageFormContent" style="border:1px solid #B8D0D6" layoutH="56">
                <table class="items itemsArea">
                    <tr>
                        <th><?php echo Yii::t('system', 'No.') ?></th>
                        <th>用户</th>
                        <th><?php echo Yii::t('aliexpress_product', 'SKU') ?></th>
                        <th>标记价格</th>
                    </tr>
                    <?php 
                        //print_r($allInfo);
                        foreach ($allInfo as $skuInfo){
                    ?>
                    <tr>
                        <td><?php echo $skuInfo['id']; ?><input type="hidden" name="skuId[]" value="<?php echo $skuInfo['id']; ?>" readonly="true" style="width:66px;" /></td>
                        <td><?php echo AliexpressAccount::model()->getAccountNameById($skuInfo['account_id']); ?></td>
                        <td><?php echo $skuInfo['sku']; ?></td>
                        <td><input type="text" name="skuPrice[]" value="" style="width:66px;" /></td>
                    </tr>
                    <?php 
                        }
                    ?>                    
                </table>
		    </div>
	 	</div>
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="button" onclick="batchUpdatePrice()" class="batchChangePriceSubmit"><?php echo Yii::t('system', 'Save')?></button>
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close AjunClose"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    
</div>
</form>
<script type="text/javascript">
    function batchUpdatePrice(){
    	var params = $(".batchChangePriceClass").serialize();
		console.log(params);
		$.ajax({
			type: 'post',
			url:  "/aliexpress/aliexpressproductpricenotice/batchchangepricedo",
			data: params,
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				} else {
					alertMsg.correct(result.message);
					$('.AjunClose').click();
				}
			},
			dataType:'json'
		});
    }
	$(document).on('click','.batchChangePriceSubmit',function(){
		
	});
</script>










