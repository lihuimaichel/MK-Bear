<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent" layoutH="70"> 
 
   <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'orderForm-grid',
            'clientOptions' => array(
        ),
    	
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(
         	'enctype'	=>'multipart/form-data',
        	'novalidate'	=>'novalidate',
        	'class'		=>'pageForm required-validate',
        	'is_dialog'	=>0,
        	'onsubmit'=>"return iframeCallback(this);",
        	'target'	=> 'dialog',
        )
    ));
?>  
<div class="pageFormContent" layoutH="50">
<table class="dataintable_inquire" width="96%" cellspacing="1" cellpadding="3" border="0">
    <tbody>
        <tr>
            <a href="/uploads/skuseller/sku和销售人员绑定关系导入模板.xlsx" target="__blank">下载模板</a>
        </tr>
    	<tr>
            <td>选择文件（xlsx格式）</td>
            <td>
                <input type="file" name="csvfilename" />
                <input type="hidden" name="type" value="export" />
            </td>
        </tr>
    </tbody>
</table>
<div class="formBar" width="96%">
        <ul> 
            <li>
        	<div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit" ><?php echo Yii::t('system', '上传')?></button>                       
                    </div>
                </div>
       	    </li>             	
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
</div>
    
    <?php $this->endWidget(); ?>
</div>