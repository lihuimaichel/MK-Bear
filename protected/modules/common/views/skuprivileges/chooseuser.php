<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
<?php 
	$form = $this->beginWidget('CActiveForm', array(
		'id' => 'skuprivileges_form_id',
		'action' => Yii::app()->createUrl($this->route),
		'enableAjaxValidation' => false,
		'enableClientValidation' => true,
		'clientOptions' => array(
			'validateOnSubmit' => true,
			'validateOnChanage' => true,
			'validateOnType' => false,
			'afterValidate' => 'js:afterValidate',
		),
		'htmlOptions' => array(
			'class' => 'pageForm',
		),
	));	
?>
	<div class="pageFormContent">
		<div class="row">
			<?php echo $form->labelEx($model, 'username');?>
			<?php echo $form->textField($model, 'username');?>
			<?php /*echo CHtml::link(Yii::t('sku_privileges', 'Choose User'), Yii::app()->createUrl('common/skuprivileges/chooseuser'), array(
				'target' => 'dialog',
				'width' => 750,
				'height' => 750,
				'mask' => true,
			));*/?>
			<?php echo $form->error($model, 'username');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'platform_id');?>
			<?php echo $form->dropDownList($model, 'platform_code', CHtml::listData(UebModel::model('platform')->findAll(), 'platform_code', 'platform_name'), array(
				'empty' => Yii::t('system', 'Please Select'),
				'id' => 'platform_code',
				'ajax' => array(
					'type' => 'POST',
					'url' => Yii::app()->createUrl('common/productimagesetting/getaccountlist'),
					'data' => array('platform_code' => 'js:this.value'),
					'update' => '#Skuprivileges_account_id',
					'cache' => false,
				),
			));?>
			<?php echo $form->error($model, 'platform_code');?>
		</div>
		<div class="row">
			<?php echo $form->labelEx($model, 'account_id');?>
			<?php echo $form->dropDownList($model, 'account_id', array('empty' => Yii::t('system', 'Please Select')));?>
			<?php echo $form->error($model, 'account_id');?>
		</div>
	</div>
	<div class="formBar">
		<ul>
			<li>
				<div class="buttonActive">
					<?php echo CHtml::button(Yii::t('system', 'Button Confirm'), array('onclick' => 'validateCallback("#skuprivileges_form_id", chooseUser)'));?>
				</div>
			</li>
		</ul>	
	</div>	
<?php $this->endWidget();?>
</div>
<script type="text/javascript">
function chooseUser(data) {
	if (data.statusCode != '200') {
		alertMsg.error(data.message);
	} else {
		$.pdialog.closeCurrent();
		var url = data.url;
		navTab.openTab('tabCreatePrivileges', url, {title: '<?php echo Yii::t('sku_privileges', 'Add Sku Privileges');?>'});
		//$.pdialog.open(url, 'test', 'test', {width: 750, height: 750});
	}
}
</script>