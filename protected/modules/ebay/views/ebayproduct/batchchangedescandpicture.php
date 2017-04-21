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
        'action' => '/ebay/ebayproduct/updatedesc',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="bg14 pdtb2 dot">
	         <strong>所选itemID操作</strong>           
	    </div>
        <div class="pd5" style="height:120px;">
			<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
            <div class="row">
                <?php  echo $form->labelEx($model,'原因');?>
                <?php  echo $form->dropDownList($model,'seller_id',$sellerList);?>
                <?php  echo $form->error($model,'seller_id',array('style' => 'float: right;'));?>
            </div>
            
            <div class="row">
                <input type="checkbox" name="types[]" value="1" style="float: left;" checked id="batch_update_desc_picture_types_1">
                <label for="batch_update_desc_picture_types_1" >修改描述和里面图片</label>
                
            </div>
            <div class="row">
            	<input type="checkbox" name="types[]" value="2" style="float: left;" id="batch_update_desc_picture_types_2">
            	<label for="batch_update_desc_picture_types_2" >修改主图片</label>
            </div>
            <!-- 
            <div class="row">
            	<input type="checkbox" name="types[]" value="4" style="float: left;" id="batch_update_desc_picture_types_4">
                <label for="batch_update_desc_picture_types_4" >修改标题</label>
            </div>
             -->
            
                
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