<?php // echo $this->renderPartial('_form', array('model' => $model, 'action' => 'update')); ?>
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
        <div class="pd5" style="height:130px;">
            <div class="row">
	            <?php echo $form->labelEx($model, 'email'); ?>
	            <?php echo $form->textField($model, 'email',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'email'); ?>
        	</div>
        	<div class="row">
        		<?php echo $form->labelEx($model, 'store_level');?>
        		<?php echo $form->dropDownList($model, 'store_level',UebModel::model('EbayAccount')->getStoreLevel(),array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'store_level');?>
        	</div>
        	<div class="row">
        		<?php echo $form->labelEx($model, 'frozen_status');?>
        		<?php echo $form->dropDownList($model, 'is_lock',UebModel::model('Ebayaccountmanage')->getAccountLockStatus(),array('empty'=>Yii::t('system', 'Please Select')));?>
        		<?php echo $form->error($model,'frozen_status');?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'commission'); ?>
	            <?php echo $form->textField($model, 'commission',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'commission'); ?>
        	</div>
        </div>
            
        <!--     
        <div class="bg14 pdtb2 dot" style="height:30px;">
            <strong><?php echo Yii::t('system', 'Function Setting')?></strong>           
        </div>
         -->
         
        <div class="pd5" style="height:150px;">
	        <div class="row">
	        	<?php echo $form->labelEx($model,'add_qty');?>
	        	<?php echo $form->textField($model, 'add_qty',array('size'=>28,'maxlength'=>128,'empty'=>Yii::t('system','Publish Count Can not empty'))); ?>
	        	<?php echo $form->error($model,'add_qty');?>
	        </div>
			<div class="row">
	        	<?php echo $form->labelEx($model,'auto_revise_qty');?>
	        	<?php echo $form->dropDownList($model,'auto_revise_qty',array("是","否"),array('empty'=>Yii::t('system','Please Select')));?>
	        	<?php echo $form->error($model,'auto_revise_qty');?>
			</div>
			<div class="row">
	        	<?php echo $form->labelEx($model,'relist_qty');?>
	        	<?php echo $form->textField($model, 'relist_qty',array('size'=>28,'maxlength'=>128,'empty'=>Yii::t('system','Relist Count Can not empty'))); ?>
	        	<?php echo $form->error($model,'relist_qty');?>
	        </div>
	        

	        <div class="row">
	        	<?php echo $form->labelEx($model,'is_auto_upload');?>
	        	<?php echo $form->radioButton($model, 'is_auto_upload', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'is_auto_upload', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<?php echo $form->error($model,'is_auto_upload');?>
	        </div>

	        <div class="row">
	        	<?php echo $form->labelEx($model,'is_eub');?>
	        	<?php echo $form->radioButton($model, 'is_eub', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'is_eub', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<span>系统同步订单时是否设置此账号的部分订单走E邮宝方式</span>
	        	<?php echo $form->error($model,'is_eub');?>
	        </div>
	        
	        <div class="row">
	        	<?php echo $form->labelEx($model,'is_eub_under5');?>
	        	<?php echo $form->radioButton($model, 'is_eub_under5', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'is_eub_under5', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<?php echo $form->error($model,'is_eub_under5');?>
	        </div>
	        
	        <div class="row">
	        	<?php echo $form->labelEx($model,'update_eub');?>
	        	<?php echo $form->radioButton($model, 'update_eub', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'update_eub', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<span>ebay产品管理中显示多条运费</span>
	        	<?php echo $form->error($model,'update_eub');?>
	        </div>
	        
	        <div class="row">
	        	<?php echo $form->labelEx($model,'is_restrict');?>
	        	<?php echo $form->radioButton($model, 'is_restrict', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'is_restrict', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<span>bay批量改价中永久受限账号中的广告</span>
	        	<?php echo $form->error($model,'is_restrict');?>
	        </div>
	        
	        <div class="row">
	        	<?php echo $form->labelEx($model,'is_free_shipping');?>
	        	<?php echo $form->radioButton($model, 'is_free_shipping', array('size'=>28,'maxlength'=>128, 'value'=>0)); ?><span>否</span>
	        	<?php echo $form->radioButton($model, 'is_free_shipping', array('size'=>28,'maxlength'=>128, 'value'=>1)); ?><span>是</span>
	        	<span>ebay批量改价中此账号的广告全部设成freeshipping</span>
	        	<?php echo $form->error($model,'is_free_shipping');?>
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