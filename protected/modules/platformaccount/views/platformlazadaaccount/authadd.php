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
        'action' => Yii::app()->createUrl("/platformaccount/platformlazadaaccount/reauthorization", array('id'=>$model->id)),
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
            <input name="PlatformLazadaAccount[id]" value="<?php echo $model->id; ?>" type="hidden" class="textInput">
        	<div class="row">
	            <?php echo $form->labelEx($model, 'short_name'); ?>
	        	<?php echo $form->textField($model, 'short_name',array('size'=>28, 'disabled'=>'disabled')); ?>
	           	<?php echo $form->error($model, 'short_name'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'site_id'); ?>
                <?php echo $form->dropDownList($model, 'site_id', LazadaSite::getSiteList(Yii::app()->request->getParam($model->site_id)), array('empty'=>Yii::t('system', 'Please Select'), 'disabled'=>'disabled'));?>
                <?php echo $form->error($model, 'site_id'); ?>
            </div>
        	<div class="row">
                <?php echo $form->labelEx($model, 'api_url'); ?>
                <?php echo $form->textField($model, 'api_url',array('size'=>28)); ?>
                <?php echo $form->error($model, 'api_url'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'api_key'); ?>
                <?php echo $form->textField($model, 'api_key',array('size'=>28)); ?>
                <?php echo $form->error($model, 'api_key'); ?>
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