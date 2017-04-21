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
	        <?php echo $form->labelEx($model,'tamplate_name'); ?>
            <?php echo $form->textField($model,'tamplate_name',array('size'=>20));?>
            <?php echo $form->error($model,'tamplate_name'); ?>
        </div>
        <div class="row">
	        <?php echo $form->labelEx($model,'delivery_time'); ?>
            <?php echo $form->textField($model,'delivery_time',array('size'=>15));?>
            <?php echo $form->error($model,'delivery_time'); ?>
        </div>
         <div class="row">
	        <?php echo $form->labelEx($model,'promise_template_id'); ?>
            <?php echo $form->textField($model,'promise_template_id',array('size'=>15));?>
            <?php echo $form->error($model,'promise_template_id'); ?>
        </div>
         <div class="row">
	        <?php echo $form->labelEx($model,'freight_template_Id'); ?>
            <?php echo $form->textField($model,'freight_template_Id',array('size'=>15));?>
            <?php echo $form->error($model,'freight_template_Id'); ?>
        </div>
		<div class="row">
	        <?php echo $form->labelEx($model,'product_unit'); ?>
            <?php echo $form->dropDownlist($model,'product_unit',AliexpressParamTemplate::getProductUnit(''),array('empty' => 'please select'));?>
            <?php echo $form->error($model,'product_unit'); ?>
        </div>
        <div class="row">
	        <?php echo $form->labelEx($model,'package_type'); ?>
            <?php echo $form->dropDownlist($model,'package_type',AliexpressParamTemplate::model()->getPackageTypeConfig(''),array('empty' => 'please select'));?>
            <?php echo $form->error($model,'package_type'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'reduce_strategy'); ?>
        	<?php echo $form->dropDownlist($model,'reduce_strategy',AliexpressParamTemplate::getReduceStrategy(''),array('empty' => 'please select'));?>
        	<?php echo $form->error($model,'reduce_strategy'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'ws_valid_num'); ?><font color="blue">取值范围:1-30,单位:天</font>
        	<?php echo $form->textField($model,'ws_valid_num',array('size'=>15)); ?>
        	<?php echo $form->error($model,'ws_valid_num'); ?>
        </div>
       <div class="row">
        	<?php echo $form->labelEx($model,'bulk_order'); ?><font color="blue">(取值范围2-100000,批发最小数量和批发折扣需同时有值或无值)</font>
        	<?php echo $form->textField($model,'bulk_order',array('size'=>15)); ?>
        	<?php echo $form->error($model,'bulk_order'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'bulk_discount'); ?><font color="blue">(取值范围:1-99,如,打68折,则填32)</font>
        	<?php echo $form->textField($model,'bulk_discount',array('size'=>15)); ?>
        	<?php echo $form->error($model,'bulk_discount'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'stock_num'); ?><font color="blue">(取值范围:1-10000)</font>
        	<?php echo $form->textField($model,'stock_num',array('size'=>15)); ?>
        	<?php echo $form->error($model,'stock_num'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'template_status'); ?>
        	<?php echo $form->dropDownlist($model,'template_status',AliexpressParamTemplate::model()->getUseStatusConfig(),array('empty'=>'please select') ); ?>
        	<?php echo $form->error($model,'template_status'); ?>
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
