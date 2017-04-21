<?php 
Yii::app()->clientscript->scriptMap['jquery.js'] = false; 
Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/css/chosen.css', 'screen');
Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/js/custom/chosen.jquery.js');
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'logisticsinformationForm',
    'enableAjaxValidation' => false,  
    'enableClientValidation' => true,
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
      //  'additionValidate' => 'js:check',
        'afterValidate'=>'js:afterValidate',
    ),
    // 'action' => Yii::app()->createUrl($this->route), 
    'htmlOptions' => array(        
        'class' => 'pageForm',
    	//'onsubmit'=>'return saveType(this);',
    	)
));
?>  
<div class="pageFormContent" >            
    <div class="bg14 pdtb2 dot" >    		 		
            <strong><?php echo Yii::t('product', 'Logistics infomation');?></strong>           
    </div>
    <div class="dot7 pd5" >
     <div class="row" >
            <?php echo $form->labelEx($model, 'gross_product_weight'); ?>
            <?php echo $form->textField($model, 'gross_product_weight', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            <?php echo $form->error($model, 'gross_product_weight'); ?> 
         </div>
          <div class="row" >
            <?php echo $form->labelEx($model, 'product_weight'); ?>
            <?php echo $form->textField($model, 'product_weight', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            <?php echo $form->error($model, 'product_weight'); ?> 
         </div>  
          <div class="row" >
            <?php echo $form->labelEx($model, 'product_freight'); ?>
            <?php echo $form->textField($model, 'product_freight', array( 'size' => 10)); ?>
            <?php echo $form->error($model, 'product_freight'); ?> 
         </div>  
         <div class="row">
        	<?php echo $form->labelEx($model, 'product_size'); ?>
            <?php echo $form->labelEx($model, 'product_length',array('style'=>'text-align:left;width:20px;')); ?>
            <?php echo $form->textField($model, 'product_length', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            
            <?php echo $form->labelEx($model, 'product_width',array('style'=>'text-align:left;width:20px;'));?>
            <?php echo $form->textField($model, 'product_width', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            
            <?php echo $form->labelEx($model, 'product_height',array('style'=>'text-align:left;width:20px;')); ?>
            <?php echo $form->textField($model, 'product_height', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            <?php echo $form->error($model, 'product_height'); ?>
            <?php echo $form->error($model, 'product_length'); ?> 
            <?php echo $form->error($model, 'product_width'); ?> 
         </div>
         <div class="row">
        	<?php echo $form->labelEx($model, 'pack_size'); ?>
            <?php echo $form->labelEx($model, 'pack_product_length',array('style'=>'text-align:left;width:20px;')); ?>
            <?php echo $form->textField($model, 'pack_product_length', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            
            <?php echo $form->labelEx($model, 'pack_product_width',array('style'=>'text-align:left;width:20px;'));?>
            <?php echo $form->textField($model, 'pack_product_width', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            
            <?php echo $form->labelEx($model, 'pack_product_height',array('style'=>'text-align:left;width:20px;')); ?>
            <?php echo $form->textField($model, 'pack_product_height', array( 'size' => 10, 'inc_sub_size' => 1)); ?>
            <?php echo $form->error($model, 'pack_product_height'); ?>
            <?php echo $form->error($model, 'pack_product_length'); ?>
            <?php echo $form->error($model, 'pack_product_width'); ?> 
         </div>
        
        <div id ="baozhuan" style="<?php if ($model->getAttribute('original_material_type_id') == '0'):?>display:block;<?php else:?>display:none;<?php endif;?>">
	        <div class="row">
	        	<?php echo $form->labelEx($model, 'product_pack_code'); ?>     
	        	<?php echo $form->dropDownList($model, 'product_pack_code', $productPackage['baocai'],array( 'empty' => Yii::t('system', 'Please Select'),'style'=>'width:150px;')); ?>
	        </div>
	        <div class="row">
	        	<?php echo $form->labelEx($model, 'product_package_code'); ?>  
	        	<?php echo $form->dropDownList($model, 'product_package_code', $productPackage['baozhuang'],array( 'empty' => Yii::t('system', 'Please Select'),'style'=>'width:150px;')); ?>
	        </div>

	        <div class="row">
	        	<?php echo $form->labelEx($model, 'product_label_proces'); ?>     
	        	<?php echo $form->dropDownList($model, 'product_label_proces', $productPackage['labelproces'],array( 'empty' => Yii::t('system', 'Please Select'),'style'=>'width:150px;')); ?>
	        </div>
        </div>

	        <div class="row">
	            <?php echo $form->labelEx($model, 'product_original_package'); ?>
	            <span style="float:left;">
	            <?php echo $form->checkBox($model, 'product_original_package');?>
	            </span>
	            <?php echo $form->error($model, 'product_original_package'); ?> 
	        </div>   
	        <div class="row">
	            <?php echo $form->labelEx($model, 'product_is_storage'); ?>
	            <?php echo $form->checkBox($model, 'product_is_storage');?>
	            <?php echo $form->error($model, 'product_is_storage'); ?> 
	        </div>  
	        
    </div>
    </div>
<div class="formBar">
    <ul>
   		<li>
            <div class="button"><div class="buttonContent"><button type="button" class="close" onclick="$.pdialog.closeCurrent();">
            <?php echo Yii::t('system', 'Closed')?></button></div></div>
        </li>
    </ul>
</div>
<?php $this->endWidget(); ?>
<script language="javascript">
	$(document).ready(function() {
	    $("input", $.pdialog.getCurrent()).attr("readonly",true);
	    $(":checkbox", $.pdialog.getCurrent()).attr("disabled",true);
	    $("select", $.pdialog.getCurrent()).attr("disabled",true);
	    $(".db", $.pdialog.getCurrent()).css("display",'none');
	    $(".search-choice-close", $.pdialog.getCurrent()).css("display",'none');
	});
</script>