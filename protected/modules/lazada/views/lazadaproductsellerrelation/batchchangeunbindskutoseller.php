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
        'action' => '/lazada/lazadaproductsellerrelation/savebatchsetunbindskutoseller',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>  
    <div class="pageFormContent" layoutH="56"> 
        <div class="bg14 pdtb2 dot">
	         <strong>所选SKU改为销售人员</strong>           
	    </div>
        <div class="pd5" style="height:120px;">
			<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
            <div class="row">
                <?php  echo $form->labelEx($model,'seller_id');?>
                <?php  echo $form->dropDownList($model,'seller_id',$sellerList);?>
                <?php  echo $form->error($model,'seller_id',array('style' => 'float: right;'));?>
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