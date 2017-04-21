<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
	<?php
	$form = $this->beginWidget('CActiveForm', array(
		'id' => 'sale_price_scheme_id',
		'action' => array(Yii::app()->createUrl('common/salepricescheme/create')),
		'enableAjaxValidation' => false,
		'enableClientValidation' => true,
		'clientOptions' => array(
			'validateOnSubmit' => true,
			'validateOnChange' => true,
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
		<?php 
			echo $form->labelEx($model, 'scheme_name');
			echo $form->textField($model, 'scheme_name', array('size' => 22));
			echo $form->error($model, 'scheme_name');
		?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'platform_code');?>
			<?php echo $form->dropDownList($model, 'platform_code', CHtml::listData(UebModel::model('Platform')->findAll(), 'platform_code', 'platform_name'), array('empty' => Yii::t('system', 'Please Select')));?>
			<?php echo $form->error($model, 'platform_code');?>
		</div>		
		<div class="row">
		<?php 
			echo $form->labelEx($model, 'profit_calculate_type');
			echo $form->dropDownList($model, 'profit_calculate_type', SalePriceScheme::getProfitCalculateTypeList(), array(
				'empty' => Yii::t('system', 'Please Select'),
			));
			echo $form->error($model, 'profit_calculate_type');
		?>
		</div>		
		<div class="row">
		<?php 
			echo $form->labelEx($model, 'standard_profit_rate');
			echo $form->textField($model, 'standard_profit_rate', array('size' => 6));
			echo $form->error($model, 'standard_profit_rate');
		?>
		</div>
		<div class="row">
		<?php 
			echo $form->labelEx($model, 'lowest_profit_rate');
			echo $form->textField($model, 'lowest_profit_rate', array('size' => 6));
			echo $form->error($model, 'standard_profit_rate');
		?>		
		</div>
		<div class="row">
		<?php 
			echo $form->labelEx($model, 'floating_profit_rate');
			echo $form->textField($model, 'floating_profit_rate', array('size' => 6));
			echo $form->error($model, 'standard_profit_rate');
		?>		
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
	<?php $this->endWidget();?>
</div>