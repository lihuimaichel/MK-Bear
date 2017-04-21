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
        'action' => Yii::app()->createUrl("/platformaccount/platformlazadaaccount/addsave"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:240px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'account_id'); ?>
                <?php echo $form->dropDownList($model, 'account_id', PlatformLazadaAccount::getAccount(), array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'account_id'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'short_name'); ?>
	        	<?php echo $form->textField($model, 'short_name',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'short_name'); ?>
        	</div>
			<div class="row">
	            <?php echo $form->labelEx($model, 'email'); ?>
                <?php echo $form->textField($model, 'email',array('size'=>48)); ?>
                <?php echo $form->error($model, 'email'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'site_id'); ?>
                <?php echo $form->dropDownList($model, 'site_id', $siteList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'site_id'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'department_id'); ?>
                <?php echo $form->dropDownList($model, 'department_id', $departmentList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'department_id'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'api_url'); ?>
                <?php echo $form->textField($model, 'api_url',array('size'=>48)); ?>
                <?php echo $form->error($model, 'api_url'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'api_key'); ?>
                <?php echo $form->textField($model, 'api_key',array('size'=>48)); ?>
                <?php echo $form->error($model, 'api_key'); ?>
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