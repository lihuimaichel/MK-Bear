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
        'action' => Yii::app()->createUrl("wish/wishspecialorderaccount/savedata"),
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
        	<?php if (isset($data)):?>
        	<input type="hidden" name="id" value="<?php echo $data['id'];?>"> 
        	<?php endif;?>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'buyer_id'); ?>
	            <?php echo $form->textField($model, 'buyer_id',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'buyer_id'); ?>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model,   'buyer_email'); ?>
	            <?php echo $form->textField($model, 'buyer_email',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'buyer_email'); ?>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model, 'buyer_phone'); ?>
	            <?php echo $form->textField($model, 'buyer_phone',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'buyer_phone'); ?>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model, 'paypal_id'); ?>
	            <?php echo $form->textField($model, 'paypal_id',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'paypal_id'); ?>
        	</div>

        	<div class="row">
        		<?php echo $form->labelEx($model, 'status');?>
        		<?php echo $form->dropDownList($model, 'status', UebModel::model('WishSpecialOrderAccount')->getStatusOptions(), array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'status');?>
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