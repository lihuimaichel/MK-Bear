<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'LazadaParamTemplateForm',
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'focus' => array($model, ''),
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        'afterValidate' => 'js:afterValidate',
    	//'additionValidate' => 'js:checkOption',
    ),
    'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
    'htmlOptions' => array(
        'class' => 'pageForm',
    )
));
?>

<div class="pageContent"> 
	<div class="pageFormContent" layoutH="55">
		<div class="row">
	        <?php echo $form->labelEx($model,'tpl_name'); ?>
            <?php echo $form->textField($model,'tpl_name',array('size'=>80));?>
            <?php echo $form->error($model,'tpl_name'); ?>
        </div>
		<div class="row">
	        <?php echo $form->labelEx($model,'taxes'); ?>
            <?php echo $form->dropDownlist($model,'taxes',LazadaParamTemplate::getTaxes(''),array('empty' => 'please select'));?>
            <?php echo $form->error($model,'taxes'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'shipping_time_min'); ?>
        	<?php echo $form->textField($model,'shipping_time_min',array('size'=>15)); ?>
        	<?php echo $form->error($model,'shipping_time_min'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'shipping_time_max'); ?>
        	<?php echo $form->textField($model,'shipping_time_max',array('size'=>15)); ?>
        	<?php echo $form->error($model,'shipping_time_max'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'warranty_type'); ?>
        	<?php echo $form->dropDownlist($model,'warranty_type',LazadaParamTemplate::getWarrantyType(''),array('empty'=>'please select')); ?>
        	<?php echo $form->error($model,'warranty_type'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'warranty_period'); ?>
        	<?php echo $form->dropDownlist($model,'warranty_period',LazadaParamTemplate::getWarrantyPeriod(''),array('empty'=>'please select')); ?>
        	<?php echo $form->error($model,'warranty_period'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'return_policy'); ?>
        	<?php echo $form->textArea($model,'return_policy', array('rows'=>5,'cols'=>70)); ?>
        	<?php echo $form->error($model,'return_policy'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'buyer_protection_details'); ?>
        	<?php echo $form->textArea($model,'buyer_protection_details', array('rows'=>5,'cols'=>70)); ?>
        	<?php echo $form->error($model,'buyer_protection_details'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'manufacturer'); ?>
        	<?php echo $form->textField($model,'manufacturer',array('size'=>20)); ?>
        	<?php echo $form->error($model,'manufacturer'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'image_width'); ?>
        	<?php echo $form->textField($model,'image_width',array('size'=>10)); ?>
        	<?php echo $form->error($model,'image_width'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'image_height'); ?>
        	<?php echo $form->textField($model,'image_height',array('size'=>10)); ?>
        	<?php echo $form->error($model,'image_height'); ?>
        </div>
        <div class="row">
        	<?php if( $action == 'update' ):?>
        	<?php echo $form->labelEx($model,'is_enable'); ?>
        	<?php echo $form->dropDownlist($model,'is_enable',LazadaParamTemplate::model()->getUseStatusConfig(),array('empty'=>'please select'),array('options' => array($model->is_enable => array('selected'=>true))) ); ?>
        	<?php echo $form->error($model,'is_enable'); ?>
        	<?php endif;?>
        </div>
	</div>
	<div class="formBar">
	    <ul>
	    	<li>
	        	<div class="buttonActive">
	            	<div class="buttonContent">
	                	<button type="submit"><?php echo Yii::t('system', 'Save') ?></button>
	            	</div>
	            </div>
	        </li>
	        <li>
	            <div class="button">
	                <div class="buttonContent">
	                    <button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button>
	                </div>
	            </div>
	    	</li>
		</ul>
	</div>
	<?php $this->endWidget(); ?>
</div>

<script type="text/javascript">

</script>
