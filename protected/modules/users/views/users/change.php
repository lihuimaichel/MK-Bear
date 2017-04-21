<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, 'new_password'),
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
        <div class="row">
            <?php echo $form->labelEx($model, 'new_password'); ?>
            <?php echo $form->passwordField($model, 'new_password', array( 'size' => 20)); ?>
            <?php echo $form->error($model, 'new_password'); ?>          
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
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>


