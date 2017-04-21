<table class="dataintable" width="400" cellspacing="1" cellpadding="3" border="0" align="left">
    <tbody id='catTableTbody'>
        <?php $key = 0;?>
        <?php if (! empty($categoryAttribute)):?>
        <?php foreach ($categoryAttribute as $key => $val):?>
            <tr>
                <td class="category-attr-td" width="50px"  >
                    <?php echo Yii::t('products', 'Attribute');?>               
                </td>
                <td class="category-attr-td" width="80px" >               
                    <?php echo CHtml::dropDownList("categoryAttribute[attribute_id][$key]", $val->attribute_id,UebModel::model('productAttribute')->queryPairs('id,attribute_name', 'attribute_is_public = 0'), array( 'empty' => Yii::t('system', 'Please Select'), 'validation' => 'ProductCategory'));?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo Yii::t('products', 'Is required')?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo CHtml::checkBox("categoryAttribute[attribute_is_required][$key]", $val->attribute_is_required)?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo Yii::t('system', 'Order')?>
                </td>
                <td class="category-attr-td"  width="50px">
                	<?php echo CHtml::textField("categoryAttribute[attribute_sort][$key]", $val->attribute_sort, array( 'size' => 4))?>
                </td>
                <td>
                    <a class="btn-delete-attr" onclick="delCategoryAttr(this,<?php echo $key?>,<?php echo $category_id?>);" href="javascript:void(0);" ><?php echo Yii::t('system', 'Delete')?></a>
                </td>
            </tr> 
        <?php endforeach;?>
        <?php else:?>
            <tr>
                <td class="category-attr-td" width="50px"  >
                    <?php echo Yii::t('products', 'Attribute');?>               
                </td>
                <td class="category-attr-td" width="80px" >               
                    <?php echo CHtml::dropDownList("categoryAttribute[attribute_id][0]", '',UebModel::model('productAttribute')->queryPairs('id,attribute_name', 'attribute_is_public = 0'), array( 'empty' => Yii::t('system', 'Please Select'), 'validation' => 'ProductCategory'));?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo Yii::t('products', 'Is required')?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo CHtml::checkBox("categoryAttribute[attribute_is_required][0]")?>
                </td>
                <td class="category-attr-td"  width="50px">
                    <?php echo Yii::t('system', 'Order')?>
                </td>
                <td class="category-attr-td"  width="50px">
                	<?php echo CHtml::textField("categoryAttribute[attribute_sort][0]", '', array( 'size' => 4))?>
                </td>
                <td>
                    <a class="btn-delete-attr" onclick="delCategoryAttr(this,0,<?php echo $category_id?>);" href="javascript:void(0);"><?php echo Yii::t('system', 'Delete')?></a>                   
                </td>
            </tr> 
        <?php endif;?>
    </tbody>
</table>
<div style='margin-left:5px;margin-top:10px;display:none;height:20px;width:150px;' id="ProductCategory_em" class="errorMessage" ></div>
<p id="add-attr-line" align="center">
    <br/>
    <a id="btn-add-attr" onclick="addCategoryAttr(this,<?php echo $category_id?>);" href="javascript:void(0);">
        <?php echo Yii::t('products', 'Add a attribute')?>
    </a>
</p>
<script>
     var index = parseInt('<?php echo $key;?>');
     var addCategoryAttr = function(obj,category_id) {
        var parentObj = $(obj).parent().parent();
        index++;
        $.ajax({
            type: "post",
            url: "/products/productcat/getattr",
            data: {index: index,
            	   category_id: category_id},
            async: false,   
            dataType:'html',
            success: function(data) {
               if ( data ) {                  
                   $('#catTableTbody').append(data);
               }
            }
        });               
    }  
    var delCategoryAttr = function(obj,id,category_id) {
    	var select_id = $('#categoryAttribute_attribute_id_'+id).val();

        $.ajax({
            type: "post",
            url: "/products/productcat/getIsUsedStatus",
            data: {select_id: select_id,
            	   category_id: category_id},
            async: false,   
            dataType:'html',
            success: function(data) {
               if(data){
            	   $('#category_attr_div').show();
       		   	   $('#category_attr_div').html($.regional.attr.msg.attrIsUsed);
               }else{
            	   $('#category_attr_div').hide();
            	   $(obj).parent().parent().remove();
               }
            }
        });   
// 		var selectedArr = [];
//         $('select[name^="categoryAttribute"]').each(function(i) {            
//         	selectedArr[i] = $(this).val();
// 		});  
// 		var is_exist = false;
// 		for(var p=0;p<selectedArr.length;p++){
// 			if(selectedArr[p]==selectedArr[p+1]){
// 				is_exist = true;
// 			}
// 		}
//         if(is_exist){
// 			$('#category_attr_div').show();
// 			$('#category_attr_div').html($.regional.attr.msg.attrCanontbeSelected);
// 		}else{
// 			$('#category_attr_div').hide();
// 		}
    }
    
    var checkcategoryAttribute = function(obj){
		var selected = $('#'+obj.id+' option:selected').val();

		var num=0;
		$('select[name^="categoryAttribute"]').each(function(i) {            
			if($(this).val()==selected){
				num++;
	        }
		});  
		if(num>=2){
			$('#category_attr_div').show();
			$('#category_attr_div').html($.regional.attr.msg.attrCanontbeSelected);
			$('#'+obj.id).val('');
			
		}else{
			$('#category_attr_div').hide();
		}
    }
</script>
