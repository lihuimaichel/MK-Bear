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
        'action' => '/aliexpress/aliexpressproduct/savebatchmodifyfreighttemplate',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:40px;">
			<div class="row">
                <input type="hidden" name="account_id" value="<?php echo $accountId; ?>">
                <input type="hidden" name="ids" value="<?php echo $ids; ?>">
                <label>选择的账号名称</label>
                <span style="line-height:16px;padding-top:5px;display:block;"><?php echo $accountName; ?></span>
            </div>
            <div class="row">
                <?php  echo $form->labelEx($model,'freight_template_id');?>
                <?php  echo $form->dropDownList($model,'freight_template_id',$freightTemplateInfo);?>
                <?php  echo $form->error($model,'freight_template_id',array('style' => 'float: right;'));?>
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