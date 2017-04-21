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
            'afterValidate' => 'js:afterValidate',
        ),      
    ));

?>
    <div class="pageFormContent" layoutH="80">  
          <div class="row">
            <?php echo $form->labelEx($model, 'account_name'); ?>
            <?php echo $form->textField($model, 'account_name', array( 'size' => 20)); ?>
            <?php echo $form->error($model, 'account_name'); ?>
         </div>  
	</div>
 	<div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', 'Save') ?></button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>




