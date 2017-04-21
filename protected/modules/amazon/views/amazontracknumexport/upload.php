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
        	'is_dialog'	=>1,
        )
    ));
?>  
<div class="pageFormContent" >
<table class="dataintable_inquire" width="800px" cellspacing="1" cellpadding="3" border="0">
    <tbody>
        <tr>
            <a href="<?php echo Yii::app()->createUrl($this->route, array('type' => 'downloadtp')); ?>" style="color: green;"> 下载模板</a>
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
</div>
    <div class="formBar" style="width: 800px;">
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
    <?php $this->endWidget(); ?>
</div>