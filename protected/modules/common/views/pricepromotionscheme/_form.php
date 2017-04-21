<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
<?php 
	$form = $this->beginWidget('ActiveForm', array(
		'id' => 'promotion_scheme_form',
		'enableClientValidation' => true,
		'enableAjaxValidation' => false,
		'action' => Yii::app()->createUrl($this->route),
		'clientOptions' => array(
			'validateOnChange' => true,
			'validateOnSubmit' => true,
			'validateOnType' => false,
			'afterValidate' => 'js:afterValidate',
		),
		'htmlOptions' => array(
			'class' => 'pageForm',
		),
	));
?>
	<div class="pageFormContent" layoutH="65">
		<div class="row">
			<?php echo $form->labelEx($model, 'name');?>
			<?php echo $form->textField($model, 'name', array('size' => '55'));?>
			<?php echo $form->error($model, 'name');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'platform_code');?>
			<?php echo $form->listBox($model, 'platform_code', $platformList, array('multiple' => 'multiple', 'style' => 'width:155px;height:120px;'));?>
			<?php echo $form->error($model, 'platform_code');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'discount_mode');?>
			<?php echo $form->dropDownList($model, 'discount_mode', PricePromotionScheme::getDiscountModeList(), array('onchange' => 'currencyController(this.value)'));?>
			<?php echo $form->error($model, 'discount_mode');?>
		</div>
		<div class="row" id="currencyRow" style="display: none;">
			<?php echo $form->labelEx($model, 'currency_code');?>
			<?php echo $form->dropDownList($model, 'currency_code', Currency::model()->getCurrencyList());?>
			<?php echo $form->error($model, 'currency_code');?>
		</div>		
		<div class="row">
			<?php echo $form->labelEx($model, 'discount_factor');?>
			<?php echo $form->textField($model, 'discount_factor');?>
			<?php echo $form->error($model, 'discount_factor');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'status');?>
			<?php echo $form->dropDownList($model, 'status', PricePromotionScheme::getStatusList());?>
			<?php echo $form->error($model, 'status');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'start_date');?>
			<?php echo $form->textField($model, 'start_date', array('class' => 'date', 'dateFmt' => 'yyyy-MM-dd HH:mm:ss',));?>
			<?php echo $form->error($model, 'start_date');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'end_date');?>
			<?php echo $form->textField($model, 'end_date', array('class' => 'date', 'dateFmt' => 'yyyy-MM-dd HH:mm:ss',));?>
			<?php echo $form->error($model, 'end_date');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'note');?>
			<?php echo $form->textArea($model, 'note', array('cols' => '48', 'rows' => '8'));?>
			<?php echo $form->error($model, 'note');?>
		</div>		
	</div>
	<div class="formBar">
		<ul>
			<li>
				<div class="buttonActive">
					<div class="buttonContent">
						<?php echo CHtml::submitButton(Yii::t('system', 'Save'));?>
					</div>
				</div>
			</li>
		</ul>
	</div>	
<?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
function currencyController(type) {
	if (type == 2)
		$('#currencyRow').show();
	else
		$('#currencyRow').hide();
}
</script>
