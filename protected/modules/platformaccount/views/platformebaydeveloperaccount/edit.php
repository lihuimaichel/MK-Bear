<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl("/platformaccount/platformebaydeveloperaccount/edit", array('id'=>$model->id)),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:240px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'account_name'); ?>
                <?php echo $form->textField($model, 'account_name',array('size'=>48)); ?>
                <?php echo $form->error($model, 'account_name'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'appid'); ?>
	        	<?php echo $form->textField($model, 'appid',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'appid'); ?>
        	</div>
			<div class="row">
	            <?php echo $form->labelEx($model, 'devid'); ?>
                <?php echo $form->textField($model, 'devid',array('size'=>48)); ?>
                <?php echo $form->error($model, 'devid'); ?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'certid'); ?>
                <?php echo $form->textField($model, 'certid',array('size'=>48)); ?>
                <?php echo $form->error($model, 'certid'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'ru_name'); ?>
                <?php echo $form->textField($model, 'ru_name',array('size'=>48)); ?>
                <?php echo $form->error($model, 'ru_name'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'max_nums'); ?>
                <?php echo $form->textField($model, 'max_nums',array('size'=>48)); ?>
                <?php echo $form->error($model, 'max_nums'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'status'); ?>
                <?php echo $form->dropDownList($model, 'status', $accountStatusList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'status'); ?>
            </div>
        </div>               
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button>                     
                    </div>
                </div>
            </li>              
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close">关闭</button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>