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
        'action' => Yii::app()->createUrl("/lazada/lazadaproductsellerrelation/savedata", array('id'=>$model->id)),
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
        	<input type="hidden" name="id" value="<?php echo $model->id;?>"/>
        	<div class="row">
	            <label for="account_id">账号</label>
	            <input type="text" id="account_id" value="<?php echo $model->account_name;?>" readonly/>
        	</div>
         	<div class="row">
	            <label for="item_id">ItemID</label>
	            <input type="text" id="item_id" value="<?php echo $model->item_id;?>" readonly/>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'sku'); ?>
	        	<?php echo $form->textField($model, 'sku',array('size'=>28,'maxlength'=>128,'empty'=>"不能为空")); ?>
	           	<?php echo $form->error($model, 'sku'); ?>
        	</div>
			<div class="row">
	            <?php echo $form->labelEx($model, 'online_sku'); ?>
	        	<?php echo $form->textField($model, 'online_sku',array('size'=>28,'maxlength'=>128,'empty'=>"不能为空")); ?>
	           	<?php echo $form->error($model, 'online_sku'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'seller_id'); ?>
	            <?php echo $form->dropDownList($model, 'seller_id', $sellerList, array('empty'=>Yii::t('system', 'Please Select')));?>
	            <?php echo $form->error($model, 'seller_id'); ?>
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