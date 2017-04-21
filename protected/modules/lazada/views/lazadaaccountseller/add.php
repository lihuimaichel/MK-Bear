<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style type="text/css">
    .pd5 .row span{
        width: 110px;
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
        'action' => '/lazada/lazadaaccountseller/add',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?> 
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:120px;">
            <div class="row">
                <?php  echo $form->labelEx($model,'seller_user_id');?>
                <?php  echo $form->dropDownList($model,'seller_user_id',$sellerList);?>
                <?php  echo $form->error($model,'seller_user_id',array('style' => 'float: right;'));?>
            </div>
        	<div class="row" id="accountListAll">
                <?php if($accountList):?>
                <?php foreach ($accountList as $id=>$accout):?>
                <span><input name="LazadaAccountSeller[account_id][]" type="checkbox" value="<?php echo $id; ?>" /><?php echo $accout; ?></span>
                <?php endforeach;?>
                <?php endif;?>
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
<script type="text/javascript">
$(document).ready(function(){
    //触发了要发布的账号
    $("#LazadaAccountSeller_seller_user_id").change(function(){
        var selectedId = $(this).find("option:selected").val();
        var ajaxurl ='/lazada/lazadaaccountseller/ajaxdata/seller_id/' + selectedId;
        htmlobj=$.ajax({url:ajaxurl,async:false});
        option = htmlobj.responseText;
        // $('#accountListAll').empty();
        $('#accountListAll').html(option);
    });
});
</script>