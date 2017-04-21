<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'ConditionsFieldForm',
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
	        <?php echo $form->labelEx($model,'platform_code'); ?>
            <?php echo $form->dropDownlist($model,'platform_code',UebModel::model('Platform')->getPlatformList(),
            	array('empty' => '%(所有平台)'));?>
            <?php echo $form->error($model,'platform_code'); ?>
        </div>
		<div class="row">
	        <?php echo $form->labelEx($model,'rule_class'); ?>
            <?php echo $form->dropDownlist($model,'rule_class',TemplateRulesBase::getRuleClassList(),
            	array('empty' => '%(所有类别)'));?>
            <?php echo $form->error($model,'rule_class'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'field_name'); ?>
        	<?php echo $form->textField($model,'field_name',array('size'=>40,'readonly'=>'readonly')); ?>
        	<?php echo $form->error($model,'field_name'); ?>
        	<a href="javascript:;" onclick='selectSku(this);' style="color:blue;">猛戳选择字段</a>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'field_title'); ?>
        	<?php echo $form->textField($model,'field_title',array('size'=>30)); ?>
        	<?php echo $form->error($model,'field_title'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'validate_type'); ?>
        	<?php echo $form->dropDownlist($model,'validate_type',TemplateRulesBase::getValidateTypeList(),array('empty'=>'请选择')); ?>
        	<?php echo $form->error($model,'validate_type'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'unit_code'); ?>
        	<?php echo $form->dropDownlist($model,'unit_code',TemplateRulesBase::getCalUnitCodeList(), array('empty'=>'请选择')); ?>
        	<?php echo $form->error($model,'unit_code'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'field_type'); ?>
        	<?php echo $form->dropDownlist($model,'field_type',ConditionsField::model()->getFieldType(), array('empty'=>'请选择')); ?>
        	<?php echo $form->error($model,'field_type'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'field_default_value'); ?>
        	<?php echo $form->textField($model,'field_default_value',array('size'=>80)); ?>
        	<?php echo $form->error($model,'field_default_value'); ?>
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model,'input_msg_content'); ?>
        	<?php echo $form->textField($model,'input_msg_content',array('size'=>80)); ?>
        	<?php echo $form->error($model,'input_msg_content'); ?>
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
$p = $.pdialog.getCurrent();

function selectSku() {
	var url='/common/conditionsfield/selectField/target/dialog';
	$.pdialog.open(url, '1', ' 选择字段', {width: 350, height: 200,mask:true});
}
</script>
