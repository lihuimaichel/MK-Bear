<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<tr>
    <td class="category-attr-td" width="50px"  >
        <?php echo Yii::t('products', 'Attribute');  ?>             
	</td>
	<td class="category-attr-td" width="80px" >            
		<?php echo CHtml::dropDownList("categoryAttribute[attribute_id][$index]", '',UebModel::model('productAttribute')->queryPairs('id,attribute_name','attribute_is_public = 0'), array( 'empty' => Yii::t('system', 'Please Select'), 'validation' => 'ProductCategory'));?>
    </td>
    <td class="category-attr-td"  width="50px">
    	<?php echo Yii::t('products', 'Is required');?>
    </td>
    <td class="category-attr-td"  width="50px">
        <?php echo CHtml::checkBox("categoryAttribute[attribute_is_required][$index]");?>
    </td>
    <td class="category-attr-td"  width="50px">
        <?php echo Yii::t('system', 'Order');?>
    </td>
    <td class="category-attr-td"  width="50px">
        <?php echo CHtml::textField("categoryAttribute[attribute_sort][$index]", '', array( 'size' => 4));?>
    </td>
    <td>
    	<a class="btn-delete-attr" onclick="delCategoryAttr(this,<?php echo $index?>,<?php echo $category_id?>);" href="javascript:void(0);"><?php echo Yii::t('system', 'Delete');?></a> 
    </td>
</tr>