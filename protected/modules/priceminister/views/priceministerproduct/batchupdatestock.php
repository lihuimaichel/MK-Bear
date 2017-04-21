<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'updatestockform',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => '/priceminister/priceministerproduct/saveStock',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
    <div class="pageFormContent" layoutH="56">   
        <input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/> 
        <div class="pd5" style="height:120px;">
			<div class="row">
                <?php echo $form->labelEx($model, 'quantity_available'); ?>
                <?php echo $form->textField($model, 'quantity_available', array( 'size' => 20,'value'=>'')); ?>
                <?php echo $form->error($model, 'quantity_available'); ?> 
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
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>