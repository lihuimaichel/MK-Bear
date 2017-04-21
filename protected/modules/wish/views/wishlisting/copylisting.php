<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style type="text/css">
    .pd5 .row span{
        width: 100px;
        float: left;
    }
</style>
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
        'action' => '/wish/wishlisting/copytoproductaddsave',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong>请选择要复制的账号</strong>           
	    </div>
        <div class="pd5" style="height:120px;">
        	<div class="row">
                <?php if($accountList):?>
                <?php foreach ($accountList as $id=>$accout):?>
                <span><input name="WishListing[account_id][]" type="checkbox" value="<?php echo $id; ?>" /><?php echo $accout; ?></span>
                <?php endforeach;?>
                <?php endif;?>
        	</div>
            <div class="row">
                <?php echo $form->label($model, 'ids', array('style'=>'display:none;'));?>
                <?php echo $form->hiddenField($model, 'ids', array('value' => $ids));?>
                <?php echo $form->error($model, 'ids');?>
            </div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '复制')?></button>                     
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