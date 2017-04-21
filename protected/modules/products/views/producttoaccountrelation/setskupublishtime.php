<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'skuPublishTimeForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	//'focus' => array($mod, ''),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route,array('ids'=>$ids,'seller'=>$seller,'platform_code'=>$platform_code,'site'=>$site,'accountId'=>$accountId, 'is_multi'=>$is_multi)), 
        'htmlOptions' => array(        
            'class' 	=> 'pageForm',
        	'target'	=> 'dialog',
        	'rel'		=>	'skuPublishTimeForm',
        	'onsubmit'	=>	'return validateCallback(this, dialogAjaxDone)'         
        )
    ));
 
   // echo '<pre>';print_r($model);die;
    ?>   
    <div class="pageFormContent" layoutH="56">   
              <div class="row">
              <?php //echo CHtml::hiddenField("sku", $model['sku'], array('class'=>'textInput skuInquire','id'=>'sku','size' => 10,'readonly'=>'readonly')); ?>
             
             </div> 
              <div class="row">

				<label style="width:90px;">&nbsp;&nbsp;&nbsp;&nbsp;预计刊登时间:</label> 
				<input type="text" id="ready_publish_time" name="ready_publish_time" value="" datefmt="yyyy-MM-dd HH:mm:ss" class="date textInput">
				     
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

<script>

</script>
