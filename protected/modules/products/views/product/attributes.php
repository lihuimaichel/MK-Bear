<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<?php
$form = $this->beginWidget('CActiveForm', array(
    'id' => 'selectAttrForm',
    'enableAjaxValidation' => false,  
    'enableClientValidation' => true,
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        // 'additionValidate' => 'js:check',
        'afterValidate'=>'js:afterValidate',
    ),
    'action' => Yii::app()->createUrl($this->route),
    'htmlOptions' => array(        
        'class' => 'pageForm',         
       )
));
?>   
<style type="text/css">
<!--
    table.dataintable td{
        vertical-align:inherit;
    }
    .descriptionContent label{
        width: 120px;
        line-height: 21px;
        float: left;
        padding: 0 5px;
    }
-->
</style>
<div class="pageFormContent descriptionContent" layoutH="100">
    <div class="bg14 pdtb2 dot">
        <strong><?php echo Yii::t('product', 'Attribute');?></strong>           
    </div>
    <div class="dot7 pd5">    
      <table class="dataintable" id="dataintable" width="100%" cellspacing="1" cellpadding="3" border="0">
       <thead>
        <tr>
            <th><?php echo Yii::t('product', 'Attribute name')?></th>         
            <th><?php echo Yii::t('product', 'Attribute value name')?> <input style="display:none" type="button" id=addAttrBtn name="addAttrBtn" value="<?php echo Yii::t('system', 'Add');?>">
	            <select id="addattr" style="display: none">
	            	<option><?php echo Yii::t('system', 'Please Select');?></option>
	            	<?php 
	            	foreach ($isNopublicAttr as $val){
		            	echo '<option id="'.$val['id'].'" value="'.$val['id'].'">';
		            	echo $val['attribute_name'];
		            	echo '</option>';
	            	}?>
	            </select>	        
            	<?php 
	            	foreach ($isNopublicAttr as $val){
						echo CHtml::link('','',array('target'=>'dialog','mask'=>'1','width'=>'650','height'=>'450','id'=>'addAttrLink_'.$val['id']));
	            	}?>
            </th>
        </tr>
        </thead>
    <tbody>
      <?php 
     //  echo '<pre>';print_r($categoryAttributeList);die('aa');
      foreach ($categoryAttributeList as $key => $val) {
            switch ($val['attribute_showtype_value']) {
            	
                 case 'list_box':                    
                     echo '<tr class="row" id="attribute_'.$val['id'].'" >';                    
                     $htmlOptions = array();                   
                     if (! empty($val['attribute_is_required'])) {                       
                        $htmlOptions['class'] = 'required'; 
                        $attributeName = $val['attribute_name'] .'<span class="required">*</span>';
                     } else {
                         $attributeName = $val['attribute_name'];
                     }
                     echo '<td class="multi-attr-td" >';
                     echo CHtml::label($attributeName, $val['attribute_name'], $htmlOptions);   
                     echo '</td>';
                     echo '<td class="multi-attr-td" >';                  
                     echo CHtml::dropDownList("attr[{$val['id']}]", isset($selectAttrPairs[$val['id']]) ? $selectAttrPairs[$val['id']] : '', $attributeListData[$val['id']], array( 'empty' => Yii::t('system', 'Please Select')));                  
                     echo '</td>';
                     echo '</tr>';
                     break;
                 case 'check_box':
                     echo '<tr class="row" id="attribute_'.$val['id'].'" >';
                     echo '<td class="multi-attr-td" >';
                     echo CHtml::label($val['attribute_name']=='Product features'?'产品属性':$val['attribute_name'], $val['attribute_name']);
                     echo '</td>';
                     echo '<td class="multi-attr-td" id=attr_'.$val['id'].'>';
                     foreach ($attributeListData[$val['id']] as $key2 => $val2) {
                     	//get cn name,add by ethan 2014.8.9  	
                     	$attribute_value_name_cn = UebModel::model('ProductAttributeValueLang')->getAttributeNameByCode($val2,CN);
                        
                       //echo $val2;
                         if ( isset($selectAttrPairs[$val['id']]) && in_array($key2, (array)$selectAttrPairs[$val['id']]) ) {
                             $flag = true;
                         } else {
                             $flag = false;
                         }                        
                    echo CHtml::checkBox("attr[{$val['id']}][]", $flag, array( 'value' => $key2));
                    echo $attribute_value_name_cn;
                     }
                     echo '</td>';   
                     echo '</tr>';
                     break;
                 default:
                     break;
             }
      }?>
           <tr class="row">
      			<td class="multi-attr-td"><label for="<?php echo Yii::t('product', 'Special match product')?>"><?php echo Yii::t('product', 'Special match product')?></label></td>
      			<td>
      			<?php   echo Chtml::textArea('product_special',$productModel->product_special,array('size' =>500 ,'maxlength'=>500));?>
	   		</td>
      		</tr>
      </tbody>  
      </table> 
    </div>
  
    <br/>
    <?php if($product_is_multi!=0):?>
    <div class="bg14 pdtb2 dot">
            <strong><?php echo Yii::t('product', 'Multiple attribute');?></strong>           
    </div>
    
    <div class="dot7 pd5" style="height:auto;overflow:hidden;min-height:300px;">
    <?php if ($product_is_multi==2):?>
       <div class="row" id="multi_checkbox_group" >
             <label style="width:30px;"><?php echo Yii::t('product', 'Attribute');?></label>
            <?php foreach ($isNopublicAttr as $key => $val) {
                if ( $val['attribute_is_public'] ) {
                    continue;
                }?> 
                <label style="width:auto;margin-top:-2px;"><?php  echo $val['attribute_name']; echo CHtml::checkBox("select_multi[{$val['id']}]", '', array('value'=>$val['id'],'class'=>'box_11','disabled'=>'disabled'));?></label>
                <input type="hidden" name="noPublicAttrId" id="<?php echo $val['attribute_name'].'_attr'?>" value="<?php echo $val['id'];?>"/>
            <?php } ?>      
        </div>   
         <?php endif;?>
        <div class="row">
          <?php echo $form->labelEx($model, 'multi_sku',array( 'style' => 'width:100px;margin-top:-5px;')); ?>
          <?php echo  $form->textField($model, 'multi_sku', array( 'size' => 10,'style'=>'margin-top:2px;')); ?>
          <?php echo $form->error($model, 'multi_sku'); ?> 
       </div>       
       <div class="row" id="multi_content"></div>
    </div>
    <?php endif;?>
</div>

<div class="formBar">
    <ul>
        <li>
            <div class="button"><div class="buttonContent"><button type="button" class="close" onclick="$.pdialog.closeCurrent();"><?php echo Yii::t('system', 'Closed')?></button></div></div>
        </li>
    </ul>
</div>
<?php $this->endWidget(); ?>
<script>
    var selectMutiIds = <?php echo $selectMutiIds?>; 
    var productId = <?php  echo $productId?>;
    var product_is_multi = <?php echo $product_is_multi?>;
    
    $(function(){ 
      var attributeIds = [];
      var y=0;
            $('input[name^="select_multi"]', $.pdialog.getCurrent()).each(function(i){
                if (selectMutiIds.length > 0 &&
                    $.inArray($(this).val(), selectMutiIds) !== -1 && product_is_multi==2){                
                  attributeIds[y++] = $(this).val();   
                  $(this).attr('checked','checked');
                }
            });           
            attributeIds = attributeIds.join();
            $.ajax({
                type: "post",
                url: "/products/product/multi",
                data: {attributeIds: attributeIds, productId: productId, product_is_multi: product_is_multi, categoryId: $('#categoryId').val()},
                async: false,               
                success: function(data) {
                    $('#multi_content').html(data);             
                }
            });  
                       
        $('#multi_checkbox_group', $.pdialog.getCurrent()).find('input[type="checkbox"]').each(function(){           
            $(this).click(function(){
                multiRefresh();
            });
        });
        
    });

    // 老方法 却属性ID 并选中显示
    var multiRefresh = function() {
        var attributeIds = [];
        var y=0;
         $('#multi_checkbox_group').find('input[type="checkbox"]').each(function(i){
             if ( $(this).attr('checked') == 'checked' ) {
                 attributeIds[y++] = this.value;              
                // $('#attr_' + this.value).parent().parent().hide();
             } else {
                 $('#attr_' + this.value).parent().parent().show();
             }                        
        });   
        attributeIds = attributeIds.join();
         $.ajax({
            type: "post",
            url: "/products/product/multi",
            data: {attributeIds: attributeIds, productId: productId, product_is_multi: product_is_multi, categoryId: $('#categoryId').val()},
            async: false,               
            success: function(data) {
                $('#multi_content').html(data);             
            }
        });        
    }

    $("input", $.pdialog.getCurrent()).attr("disabled",true);
    $("select", $.pdialog.getCurrent()).attr("disabled",true);
    $('input[name^="select_multi"]', $.pdialog.getCurrent()).attr("disabled",true);

</script>