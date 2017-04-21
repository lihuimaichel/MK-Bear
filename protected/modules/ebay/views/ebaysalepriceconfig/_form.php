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
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:180px;">
        	
            <div class="row">
	            <?php echo $form->labelEx($model, 'start'); ?>
	            <?php echo $form->textField($model, 'start',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'start'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'end'); ?>
	            <?php echo $form->textField($model, 'end',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'end'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'standard_rate'); ?>
	            <?php echo $form->textField($model, 'standard_rate', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'standard_rate'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'min_rate'); ?>
	            <?php echo $form->textField($model, 'min_rate', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'min_rate'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'float_rate'); ?>
	            <?php echo $form->textField($model, 'float_rate', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'float_rate'); ?>
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