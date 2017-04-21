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
        'action' => '/wish/wishcopylistingseller/add',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:120px;">
        	<div class="row">
                <?php if($sellerList):?>
                <?php foreach ($sellerList as $id=>$seller):?>
                <?php 
                    $select = ''; 
                    if(in_array($id, $selectList)){
                        $select = 'checked="checked"';
                    }
                ?>
                <span><input name="WishCopyListingSeller[seller_user_id][]" type="checkbox" <?php echo $select; ?> value="<?php echo $id; ?>" /><?php echo $seller; ?></span>
                <?php endforeach;?>
                <?php endif;?>
        	</div>
            <div class="row" style="display:none;">
                <?php echo $form->label($model, 'create_user_id');?>
                <?php echo $form->hiddenField($model, 'create_user_id', array('value' => ''));?>
                <?php echo $form->error($model, 'create_user_id');?>
            </div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '保存')?></button>                     
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