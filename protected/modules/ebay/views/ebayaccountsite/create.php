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
        <div class="pd5" style="height:180px;">
            <div class="row">
	            <?php echo $form->labelEx($model,   'site_id'); ?>
	            <?php echo $form->textField($model, 'site_id',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'site_id'); ?>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model, 'site_name'); ?>
	            <?php echo $form->textField($model, 'site_name',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'site_name'); ?>
        	</div>
        	<div class="row">
            	<?php echo $form->labelEx($model, 'is_open'); ?>
            	<?php echo $form->dropDownList($model, 'is_open', UebModel::model('Ebaysite')->getSiteStatus(),array('empty'=>Yii::t('system','Please Select'))); ?>
            	<?php echo $form->error($model, 'is_open'); ?>
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