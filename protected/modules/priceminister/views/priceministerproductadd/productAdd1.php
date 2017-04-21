<?php if( isset($dialog) && $dialog==1 ) : ?>
<div class="pageContent">
    <div class="tabs">
        <div class="tabsContent" style="height:100%;">
            <div class="pageFormContent" style="border:none;">
                <div style="padding:15px 0;height:100px;"> 
                    <div class="row">
		                <?php echo CHtml::label('SKU', 'product_add_sku'); ?>   
                        <?php echo CHtml::textField('product_add_sku', '', array('id' => 'product_add_sku')); ?>                 
                    </div>
                </div>
	        </div>
	 	</div>
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <a rel="" href="<?php echo Yii::app()->createUrl('priceminister/priceministerproductadd/productaddstepsecond');?>" target="navTab" id="product_add_next_btn" style="display:none;">
                            <?php echo Yii::t('priceminister', 'Choose Add Info')?>
                        </a>
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
</div>
<script>
$(function(){
	$('#product_add_sku').focus();
    var page = $('#navTab ul.navTab-tab li.selected').attr('tabid');
	$('#product_add_next_btn').attr('rel',page).show();
	$(document).keyup(function(e){
		if(e.keyCode==13){
			$('#product_add_next_btn').click();
		}
	});
});
$('#product_add_next_btn').click(function(){
	$(this).attr('href','priceminister/priceministerproductadd/productaddstepsecond/sku/'+$('#product_add_sku').val());
	$('.dialog .close').click();
});
</script>
<?php else: ?>
<div id="ebay_product_add_step2"></div>
<script>
	$(function(){
		$.pdialog.open(
	    		'<?php echo Yii::app()->createUrl($this->route, array('dialog'=>1)) ?>', 
	    		'1', 
	    		'<?php echo Yii::t('ebay','Please input SKU');?>',
	    		{width:400, height:230, mask:true, fresh:true}
	    );
	});
</script>  
<?php endif;?>