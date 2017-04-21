<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style type="text/css">
    .pd5 .row{
        overflow: hidden;
    }
    #insertProfit{
        line-height: 18px;
    }
</style>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
    <div class="pageFormContent" layoutH="56">         
        <div class="bg14 pdtb2 dot">
	         <strong>SKU为<?php echo $sku; ?>现在价格为<?php echo $price; ?></strong>
             <input type="hidden" name="variationID" value="<?php echo $variationID; ?>"> 
             <input type="hidden" name="accountID" id="accountID" value="<?php echo $accountID; ?>"> 
             <input type="hidden" name="itemID" id="itemID" value="<?php echo $itemID; ?>"> 
             <input type="hidden" name="sku" id="skuVal" value="<?php echo $sku; ?>">           
	    </div>
        <div class="pd5" style="height:120px;">
			<div class="row">
                <?php echo $form->labelEx($model, 'sale_price'); ?>
                <?php echo $form->textField($model, 'sale_price', array('size' => 20)); ?>
                <?php echo $form->error($model, 'sale_price'); ?> 
            </div>
            <div class="row" id="insertProfit"></div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '更改')?></button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
$(document).ready(function(){
    var url = "/ebay/ebayproduct/getprofit";
    var accountID = $("#accountID").val();
    var itemID = $("#itemID").val();
    var sku = $("#skuVal").val();
    $("#EbayProductVariation_sale_price").change(function(){
        var price = $(this).val();
        var param = {price:price, account_id:accountID, item_id:itemID, sku:sku};
        $.post(url, param, function(data){
            if(data.statusCode == 200){
                $('#insertProfit').html(data.message);
            }else{
                $('#insertProfit').html(data.message);
            }
        }, 'json');
    });
});
</script>