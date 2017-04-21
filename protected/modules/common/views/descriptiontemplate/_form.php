<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
	<?php 
		$form = $this->beginWidget('ActiveForm', array(
			'id' => 'description_template_form',
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
			'action' => array(Yii::app()->createUrl($this->route, array('id' => $model->id))),
		));
	?>
	<div class="pageFormContent" layoutH="65">
		<div class="row">
			<?php echo $form->labelEx($model, 'template_name');?>
			<?php echo $form->textField($model, 'template_name', array('size' => 25, 'value' => $model->template_name));?>
			<?php echo $form->error($model, 'template_name');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'platform_code');?>
			<?php echo $form->dropDownList($model, 'platform_code', CHtml::listData(UebModel::model('Platform')->findAll(), 'platform_code', 'platform_name'), array('empty' => Yii::t('system', 'Please Select')));?>
			<?php echo $form->error($model, 'platform_code');?>
		</div>		
		<div class="row">
			<?php echo $form->label($model, 'title_prefix');?>
			<?php echo $form->textField($model, 'title_prefix', array('size' => 15, 'value' => $model->title_prefix));?>
			<?php echo $form->error($model, 'title_prefix');?>
		</div>
		<div class="row">
			<?php echo $form->label($model, 'title_suffix');?>
			<?php echo $form->textField($model, 'title_suffix', array('size' => 15, 'value' => $model->title_suffix));?>
			<?php echo $form->error($model, 'title_suffix');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'template_content');?>
			<?php echo $form->textArea($model, 'template_content', array('id' => 'template_content', 'rows' => '15', 'cols' => "70", 'value' => $model->template_content));?>
			<?php echo $form->error($model, 'template_content');?>
		</div>
		<?php if ($action == 'update') { ?>
		<div class="row">
			<?php echo $form->labelEx($model, 'status');?>
			<?php echo $form->radioButtonList($model, 'status', $model->getStatusList(), array('template' => '{input}{label}', 'separator' => '&nbsp;&nbsp;', 'labelOptions' => array('style' => 'display:inline;float:none;')));?>
			<?php echo $form->error($model, 'status');?>
		</div>
		<?php } ?>
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
<script type="text/javascript">
KindEditor.create('#template_content', {
	width: '80%',
});
</script>