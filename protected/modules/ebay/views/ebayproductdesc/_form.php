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
	            <?php echo $form->labelEx($model, 'template_name'); ?>
	            <?php echo $form->textField($model, 'template_name',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'template_name'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'title_prefix'); ?>
	            <?php echo $form->textField($model, 'title_prefix',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'title_prefix'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'title_suffix'); ?>
	            <?php echo $form->textField($model, 'title_suffix',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'title_suffix'); ?>
        	</div>
        	
        	<div class="row">
        		<?php echo $form->labelEx($model, 'language_code');?>
        		<?php echo $form->dropDownList($model, 'language_code', UebModel::model("Language")->getLangListarr(),array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'language_code');?>
        	</div>
        	
        	<div class="row">
        		<?php echo $form->labelEx($model, 'account_id');?>
        		<?php echo $form->dropDownList($model, 'account_id', UebModel::model("EbayAccount")->getIdNamePairs(),array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'account_id');?>
        	</div>
        	<div class="row">
        		<?php echo $form->labelEx($model, 'status');?>
        		<?php echo $form->dropDownList($model, 'status', array( '关闭', '开启'), array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'status');?>
        	</div>
        	<div class="row">
        		<?php echo $form->labelEx($model, 'template_content');?>
        		<?php echo $form->textArea($model, 'template_content', array('size'=>28,'maxlength'=>128, 'class'=>'ebayProductDescriptionTemplate'));?>
        		<?php echo $form->error($model,'template_content');?>
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

<script type="text/javascript">
var _keywords = $('input[name=search_keywords]').val();
$(function(){
	KindEditor.create('textarea.ebayProductDescriptionTemplate',{
        filterMode: false,//是否开启过滤模式
		allowFileManager: true,
		width: '86%',
		height: '450',
		afterCreate : function() {
	    	this.sync();
	    },
	    afterBlur:function(){
	        this.sync();
	    },
	});
});
</script>