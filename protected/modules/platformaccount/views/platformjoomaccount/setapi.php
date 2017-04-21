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
        'action' => Yii::app()->createUrl("/platformaccount/platformjoomaccount/setapi/id/{$model->id}"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:200px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'account_name'); ?>
                <?php echo $form->textField($model, 'account_name',array('size'=>48, 'disabled'=>'disabled')); ?>
                <?php echo $form->error($model, 'account_name'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'client_id'); ?>
	        	<?php echo $form->textField($model, 'client_id',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'client_id'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'client_secret'); ?>
                <?php echo $form->textField($model, 'client_secret',array('size'=>48)); ?>
                <?php echo $form->error($model, 'client_secret'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'redirect_uri'); ?>
                <?php echo $form->textField($model, 'redirect_uri',array('size'=>48)); ?>
                <?php echo $form->error($model, 'redirect_uri'); ?>
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
                <div class="button"><div class="buttonContent"><button type="button" class="close">关闭</button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>