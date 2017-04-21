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

<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'aliexpressEditPriceForm',
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'focus' => array($model, ''),
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        'afterValidate' => 'js:afterValidate',
    	//'additionValidate' => 'js:checkOption-afterValidate',
    ),
    'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
//	 	'action' => Yii::app()->createUrl($this->route.'/ids/'.$model->order_id),
    'htmlOptions' => array(
        'class' => 'pageForm',
    )
));

$i =1;
$items = null;
if (is_array($model->detail) && !empty($model->detail)) {
	foreach ($model->detail as $detail){
		
		$items .= "<tr>";
		$items .= "<td>".$i."</td>";
		$items .= "<td>".$detail['sku']." ";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][id]' value='".$detail['id']."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][sku]' value='".$detail['sku']."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][sku_id]' value='".$detail['sku_id']."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][aliexpress_product_id]' value='".$model->aliexpress_product_id."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][account_id]' value='".$model->account_id."'/>";
        $items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][product_id]' value='".$model->id."'/>";
		$items .= "</td>";
		$items .= "<td><input type='text' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][sku_price]' value='".$detail['sku_price']."' onkeyup='value=value.replace(/[^\d.]/g,\"\")' /></td>";
		$items .= "</tr>";
		$i++;
	}
}else {
		$items .= "<tr>";
		$items .= "<td>".$i."</td>";
		$items .= "<td>".$model->sku." ";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][id]' value='".$model->id."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][variation]' value='".$model->is_variation."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][0][sku]' value='".$model->sku."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][0][sku_id]' value='".$model->sku_id."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][0][aliexpress_product_id]' value='".$model->aliexpress_product_id."'/>";
		$items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][account_id]' value='".$model->account_id."'/>";
        $items .= "<input type='hidden' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][".($i-1)."][product_id]' value='".$model->id."'/>";
		$items .= "</td>";
		$items .= "<td><input type='text' id='AliexpressEditPrice".($i-1)."' name='AliexpressEditPrice[detail][0][sku_price]' value='".$model->product_price."' onkeyup='value=value.replace(/[^\d.]/g,\"\")' /></td>";
		$items .= "</tr>";
}
?>
<div class="pageContent">
    <div class="tabs">
	 	<div class="tabsContent" style="height:100%;">
 			<div class="pageFormContent" style="border:1px solid #B8D0D6" layoutH="56">
                <table class="items itemsArea">
                    <tr>
                        <th><?php echo Yii::t('system', 'No.') ?></th>
                        <th><?php echo Yii::t('aliexpress_product', 'SKU') ?></th>
                        <th><?php echo Yii::t('aliexpress_product', 'Sku Price') ?></th>
                    </tr>
                    <tr style="display:none;">
                    	<td>
	                    	<?php echo $form->labelEx($model, 'id'); ?>
				            <?php echo $form->textField($model, 'id', array('size' => 38)); ?>
				            <?php echo $form->error($model, 'id'); ?> 
                    	</td>
                    </tr>
                    <?php echo $items;?>
                </table>
		    </div>
	 	</div>
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button>
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

</script>
