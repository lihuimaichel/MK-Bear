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
	         <strong>修改标题</strong>
             <input type="hidden" name="productId" value="<?php echo $productId; ?>">           
	    </div>
        <div class="pd5" style="height:120px;">
			<div class="row">
                <?php echo $form->labelEx($model, 'subject'); ?>
                <?php echo $form->textField($model, 'subject', array('style'=>'width:900px;', 'value'=>$subject)); ?>
                <?php echo $form->error($model, 'subject'); ?> 
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
<script type="text/javascript">
$(document).ready(function(){
    var fixedLength = 128;
    $("#AliexpressProduct_subject_em_").css('display','block');
    var titleLength = $("#AliexpressProduct_subject").val().length;
    var residualLength = parseInt(fixedLength) - parseInt(titleLength);
    $("#AliexpressProduct_subject_em_").text('还可以输入' + residualLength + '字符');

    $("#AliexpressProduct_subject").keyup(function(){
        var newLength = $(this).val().length;
        if(parseInt(newLength) > 128){
            alertMsg.error('标题已经超过了128个字符');
        }
    });   
});
</script>