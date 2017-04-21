<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;//echo $_REQUEST['warehouseid'];?>
<div class="pageContent"> 
 
   <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'orderForm-grid',
//         'enableAjaxValidation' => false,
//         'enableClientValidation' => true,
         'clientOptions' => array(
//             'validateOnSubmit' => true,
//             'validateOnChange' => true,
//              'validateOnType' => false,
//              'afterValidate'=>'js:afterValidate',
//          		'callbackframe'		=>'aa();',
        ),
    	
        'action' => Yii::app()->createUrl('common/productimportsku/uploadfile'),
        'htmlOptions' => array(
         	'enctype'		=>'multipart/form-data',
			'onsubmit'=>"return iframeCallback(this);",
        	'novalidate'		=>'novalidate',
        	'class'		=>'pageForm required-validate',
        	'is_dialog'		=>1,
        )
    ));
?>  
<div class="pageFormContent" layoutH="70">
<p>
<label>导入订单excel：</label>
<input type="file" name="file1" "class="valid">
</p>
</div>
    <div class="formBar">
        <ul> 
        	 <li>
        		<div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit" ><?php echo Yii::t('system', 'Save')?></button>                       
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


