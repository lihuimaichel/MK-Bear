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
        'action' => Yii::app()->createUrl("/platformaccount/platformaliexpressaccount/setapi/id/{$model->id}"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:200px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'short_name'); ?>
                <?php echo $form->textField($model, 'short_name',array('size'=>48, 'disabled'=>'disabled')); ?>
                <?php echo $form->error($model, 'short_name'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'app_key'); ?>
	        	<?php echo $form->textField($model, 'app_key',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'app_key'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'secret_key'); ?>
                <?php echo $form->textField($model, 'secret_key',array('size'=>48)); ?>
                <?php echo $form->error($model, 'secret_key'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'redirect_uri'); ?>
                <?php echo $form->textField($model, 'redirect_uri',array('size'=>48)); ?>
                <?php echo $form->error($model, 'redirect_uri'); ?>
            </div>
        </div>               
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '更改')?></button>                     
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