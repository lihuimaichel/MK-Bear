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
        'action' => Yii::app()->createUrl("wish/wishspecialordershipcode/savedata"),
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
        	<?php if (isset($id)):?>
        	<input type="hidden" name="id" value="<?php echo $id;?>"> 
        	<?php endif;?>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'ship_name'); ?>
	            <?php echo $form->textField($model, 'ship_name',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'ship_name'); ?>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model,   'ship_code'); ?>
	            <?php echo $form->textField($model, 'ship_code',array('size'=>28,'maxlength'=>128));?>
	            <?php echo $form->error($model, 'ship_code'); ?>
        	</div>

        	<div class="row">
        		<?php echo $form->labelEx($model, 'status');?>
        		<?php echo $form->dropDownList($model, 'status', UebModel::model('WishSpecialOrderShipCode')->getStatusOptions(), array('empty'=>Yii::t('system', 'Please Select')));?>
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