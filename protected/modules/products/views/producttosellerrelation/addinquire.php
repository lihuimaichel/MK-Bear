<?php
if(isset($product_obj)):
$key = $key+1 ;
foreach ($product_obj as $k => $val):
?>
<tr id="<?php echo $key;?>">
	<td id='img_<?php echo $key;?>'><?php echo $val['img'];?></td>
	<td valign="middle" style="padding-top:10px;">
        <?php echo CHtml::textField("sku[]", $val['sku'], array('class'=>'textInput skuInquire','id'=>'sku_'.$key,'size' => 10)); ?>
        <?php echo CHtml::hiddenField("product_id[]",$val['id'], array('id'=>'product_id_'.$key, 'size' => 8))?>
    </td>
    <td id='title_<?php echo $key;?>'><?php echo isset($val['all_language_desc']['Chinese']) ? $val['all_language_desc']['Chinese']['title'] : '';?></td>
    <td><?php echo CHtml::textField("product_cost[]",$val['product_cost'], array('id'=>'product_cost_'.$key,'size' => 8,'readonly'=>'readonly','style'=>'border:0;background:#EFEFEF;'))?></td>
    <td id='last_quote_<?php echo $key;?>'><?php echo $val['last_inquire'];?></td>
    <td>
        <?php echo CHtml::dropDownList("currency[]", '', $currencyArr, array('id'=>'currency_'.$key,'style' => 'width:100px;')); ?>
        <?php echo CHtml::textField("price[]", "", array('id'=>'price_'.$key,'class'=>'textInput','size' => 8,'style'=>'margin-left:4px;','onblur'=>"checkPrice($key);"))?>
    </td>
    <td><a class="btnDel" onclick="removeSku(this);" href="javascript:void(0);" ><?php echo Yii::t('system', 'Delete')?></a></td>    
</tr>
<?php 
$key++;
endforeach;
else:
?>
<tr id="<?php echo $key;?>">
	<td id='img_<?php echo $key;?>'><?php echo isset($arrProductInfo['img']) ? $arrProductInfo['img'] : '--';?></td>
	<td valign="middle" style="padding-top:10px;">
        <?php echo !isset($arrProductInfo['sku'])? CHtml::textField("sku[]", isset($arrProductInfo['sku']) ? $arrProductInfo['sku'] : '', array('class'=>'textInput skuInquire','id'=>'sku_'.$key,'size' => 10,'onblur'=>"skuExists(this,$key)")):
            CHtml::textField("sku[]", isset($arrProductInfo['sku']) ? $arrProductInfo['sku'] : '', array('class'=>'textInput skuInquire','id'=>'sku_'.$key,'size' => 10,'onblur'=>"skuExists(this,$key)",'readonly'=>'readonly')); ?>
        <?php echo CHtml::hiddenField("product_id[]",isset($arrProductInfo['id']) ? $arrProductInfo['id'] : '--', array('id'=>'product_id_'.$key, 'size' => 8))?>
    </td>
    <td id='title_<?php echo $key;?>'><?php echo isset($arrProductInfo['title']) ? $arrProductInfo['title'] : '--';?></td>
    <td><?php echo CHtml::textField("product_cost[]",isset($arrProductInfo['product_cost']) ? $arrProductInfo['product_cost'] : '--', array('id'=>'product_cost_'.$key, 'size' => 8,'readonly'=>'readonly','style'=>'border:0;background:#EFEFEF;'))?></td>
    <td id='last_quote_<?php echo $key;?>'><?php echo isset($arrProductInfo['last_inquire']) ? $arrProductInfo['last_inquire'] : '--';?></td>
    <td>
        <?php echo CHtml::dropDownList("currency[]", isset($arrProductInfo['currency']) ? $arrProductInfo['currency'] : '--', $currencyArr, array('id'=>'currency_'.$key,'style' => 'width:100px;')); ?>
        <?php echo CHtml::textField("price[]", "", array('id'=>'price_'.$key,'class'=>'textInput','size' => 8,'style'=>'margin-left:4px;','onblur'=>"checkPrice($key);"))?>
    </td>
    <td><a class="btnDel" onclick="removeSku(this);" href="javascript:void(0);" ><?php echo Yii::t('system', 'Delete')?></a></td>    
</tr>
<?php endif;?>
<script>
function removeSku(obj){
	$(obj).parent().parent().remove();
	var arrSku= $(".skuInquire");
	arrSku.each(function(){
		if($(this).val() == ''){
			$('#sku_is_exist').val('0');
			return false;
		}else{
			$('#sku_is_exist').val('1');
		}
	});
}
</script>