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
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
            <strong>赠品配置</strong>
	    </div>
        <div class="pd5" style="height:120px;">        
         	<div class="row">
        		<?php echo $form->labelEx($model,'account_id');?>

                <?php if(!empty($info)):?>
                <?php echo $form->dropDownList($model,'account_id',$accountList,array('options'=>array($info['account_id']=>array('selected'=>true))));?>
                <?php else:?>
                <?php echo $form->dropDownList($model,'account_id',$accountList);?>
                <?php endif;?>

                <?php echo $form->error($model,'account_id',array('style' => 'float: right;'));?>
        	</div> 
            <div class="row">
                <?php echo $form->labelEx($model,'sku');?>
                <input type="text" name="sku" class="textInput" <?php if(!empty($info)): ?> value="<?php echo $info['sku']?>" <?php else: ?>value="" <?php endif;?> >
            </div>
            <div class="row">
                <?php echo $form->labelEx($model,'gift_sku');?>
                <input type="text" name="gift_sku" class="textInput" <?php if(!empty($info)): ?> value="<?php echo $info['gift_sku']?>" <?php else: ?>value="" <?php endif;?>>
            </div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '提交')?></button>  
                        <input type="hidden" name="id" <?php if(!empty($info)): ?> value="<?php echo $info['id']?>" <?php else: ?>value="" <?php endif;?> >  
                        <input type="hidden" name="act" value="<?php echo $act ?>">      
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