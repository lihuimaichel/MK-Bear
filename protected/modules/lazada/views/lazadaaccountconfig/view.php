<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
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
        'action' => Yii::app()->createUrl($this->route,array('id'=>$model->account_id)),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>   
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:150px;">
           <div class="row">
	        	<?php echo $form->labelEx($model,'publish_count');?>
	        	<?php echo $form->textField($model, 'publish_count',array('size'=>28,'maxlength'=>128)); ?>
	        	<?php echo $form->error($model,'publish_count');?>
	        </div>
			<div class="row">
	        	<?php echo $form->labelEx($model,'adjust_count');?>
	        	<?php echo $form->dropDownList($model,'adjust_count',array("是","否"),array('empty'=>Yii::t('system','Please Select')));?>
	        	<?php echo $form->error($model,'adjust_count');?>
			</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'publish_site_id'); ?>
                <?php echo $form->textField($model,'publish_site_id', array('size' => '20',)); ?>
                <?php echo $form->error($model, 'publish_site_id',array('style'=>'float:right;')); ?> 
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