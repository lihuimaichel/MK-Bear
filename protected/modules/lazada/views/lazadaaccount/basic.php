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
        'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:150px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'seller_name'); ?>
                <?php echo $form->textField($model,'seller_name', array('size' => '20',)); ?>
                <?php echo $form->error($model, 'seller_name',array('style'=>'float:right;')); ?> 
            </div>
        	<div class="row">
                <?php echo $form->labelEx($model, 'token'); ?>
                <?php echo $form->textField($model,'token', array('size' => '20',)); ?>
                <?php echo $form->error($model, 'token',array('style'=>'float:right;')); ?> 
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
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>