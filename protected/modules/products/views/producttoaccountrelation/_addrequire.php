<?php
//echo '<pre>';print_r($product_obj);die;
$defaultWarehouseId = 41;//UebModel::model('PurchaseSetting')->getLastConfigValue(PurchaseSetting::PARA_TYPE,'default_warehouse');
if(isset($product_obj)):
$key = $key+1 ;

foreach ($product_obj as $k => $val):
?>
<tr id="<?php echo $key;?>">
  <td ><?php //echo $val['sku'];?>
    <?php echo CHtml::textField("sku[".$val['product_id']."]", $val['sku'], array('class'=>'textInput skuInquire','id'=>'skuAll_'.$key,'size' => 10,'readonly'=>'readonly')); ?>
    </td>
	<td valign="middle" style="padding-top:10px;">
        <?php //echo CHtml::hiddenField("sku[".$val['id']."]",$val['sku'], array('id'=>'sku_'.$key, 'size' => 8))?>
		<?php echo  UebModel::model('ProductClass')->getClassInfoById($val['category_id']);?>
    </td>
    <td ><?php echo  UebModel::model('Product')->getProductStatusConfig($val['product_status']);?></td>
     <td><a class="btnDel" onclick="removeSku(this);" href="javascript:void(0);" ><?php echo Yii::t('system', 'Delete')?></a></td>    
</tr>
<?php
$key++;
endforeach;
else:
?>
<?php endif;?>
<script>
function removeSku(obj){
	$(obj).parent().parent().remove();
	var arrSku= $("#producttoaccountid .skuInquire");
	arrSku.each(function(){
		if($(this).val() == ''){
			$('#producttoaccountid #sku_is_exist').val('0');
			return false;
		}else{
			$('#producttoaccountid #sku_is_exist').val('1');
		}
	});
}
</script>