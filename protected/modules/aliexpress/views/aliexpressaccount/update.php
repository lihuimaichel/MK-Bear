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
            'afterValidate' => 'js:afterValidate',
        ),      
    ));

?>
    <div class="pageFormContent" layoutH="80">  
          <div class="row">
            <?php echo $form->labelEx($model, 'is_overseas_warehouse'); ?>
            <?php echo $form->checkBox($model, 'is_overseas_warehouse'); ?>
            <?php echo $form->error($model, 'is_overseas_warehouse'); ?> 
         </div>  
	</div>
 	<div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit" onClick="autoClickNowPage()"><?php echo Yii::t('system', 'Save') ?></button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script>

	function autoClickNowPage(){
		setTimeout(function(){
			$('.dialogHeader_c').find("a[class^='close']").click();
			$('.pagination').find("li[class^='selected']").find('a').click();
		},666);		
	}
</script>



