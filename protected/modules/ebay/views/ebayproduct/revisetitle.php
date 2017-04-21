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
             <input type="hidden" name="id" value="<?php echo $id; ?>">           
	    </div>
        <div class="pd5" style="height:120px;">
			<div class="row">
                <?php echo $form->labelEx($model, 'title'); ?>
                <?php echo $form->textField($model, 'title', array('style'=>'width:600px;', 'value'=>$title)); ?>
                <?php echo $form->error($model, 'title'); ?> 
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
    var fixedLength = 80;
    $("#EbayProduct_title_em_").css('display','block');
    var titleLength = $("#EbayProduct_title").val().length;
    var residualLength = parseInt(fixedLength) - parseInt(titleLength);
    $("#EbayProduct_title_em_").text('还可以输入' + residualLength + '字符');

    $("#EbayProduct_title").keyup(function(){
        var newLength = $(this).val().length;
        if(parseInt(newLength) > 80){
            alertMsg.error('标题已经超过了80个字符');
        }
    });   
});
</script>