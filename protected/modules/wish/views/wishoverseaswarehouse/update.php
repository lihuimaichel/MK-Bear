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
        'action' => Yii::app()->createUrl("/wish/wishoverseaswarehouse/savedata", array('id'=>$model->id)),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56">
        <div class="pd5">
        	<input type="hidden" name="id" value="<?php echo $model->id;?>"/>
        	<div class="row">
	            <label for="product_id">产品ID</label>
	            <input type="text" id="product_id" size="28" style="color:gray;" value="<?php echo $model->product_id;?>" readonly />
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'sku'); ?>
	        	<?php echo $form->textField($model, 'sku',array('size'=>28,'maxlength'=>128,'empty'=>"SKU不能为空")); ?>
	           	<?php echo $form->error($model, 'sku'); ?>
        	</div>  
            <div class="row">
                <?php echo $form->labelEx($model, 'overseas_warehouse_id'); ?>
                <?php echo $form->dropDownList($model, 'overseas_warehouse_id', $warehouseList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'overseas_warehouse_id'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'account_id'); ?>
                <?php echo $form->dropDownList($model, 'account_id', $accountList);?>
                <?php echo $form->error($model, 'account_id'); ?>
            </div>

        	<div class="row">
	            <?php echo $form->labelEx($model, 'seller_id'); ?>
	            <?php echo $form->dropDownList($model, 'seller_id', $sellerList);?>
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