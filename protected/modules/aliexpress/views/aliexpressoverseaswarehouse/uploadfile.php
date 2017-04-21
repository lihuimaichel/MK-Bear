<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;//echo $_REQUEST['warehouseid'];?>
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
        'action' => Yii::app()->createUrl('aliexpress/aliexpressoverseaswarehouse/uploadfile'),
        'htmlOptions' => array(
             'enctype'    =>'multipart/form-data',
             'onsubmit'   =>"return iframeCallback(this);",
             'novalidate' =>'novalidate',
             'class'      =>'pageForm required-validate',
             'is_dialog'  =>1,
        )
    ));    
?>  
<div class="pageFormContent" layoutH="70">
<p>
<label><?php echo Yii::t('wish_product_statistic', 'Import Overseas Warehouse Excel')?>：</label>
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


