<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent" layoutH="70"> 
 
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
            'enctype'       =>'multipart/form-data',
            'onsubmit'=>"return iframeCallback(this);",
            'novalidate'        =>'novalidate',
            'class'     =>'pageForm required-validate',
            'is_dialog'     =>1,
        )
    ));
?>  
<div class="pageFormContent" layoutH="50">
<table class="dataintable_inquire" width="96%" cellspacing="1" cellpadding="3" border="0">
    <tbody>
        <tr>
            <?php if(isset($is_oversea)){ ?>
                <a href="/uploads/batchpublish/ebay海外仓批量刊登模板.xlsx" target="__blank">下载模板</a>
                <span style="font-weight:bold;color:red">(海外仓专用)</span>
            <?php }else{ ?>
                <a href="/uploads/batchpublish/ebay批量刊登模板.xlsx" target="__blank">下载模板</a>
            <?php } ?>
        </tr>
    	<tr>
            <td>选择文件（xlsx格式）</td>
            <td>
                <input type="file" name="csvfilename" />
                <input type="hidden" name="type" value="export" />
                <input type="hidden" name="listing_duration" value="<?php echo $duration; ?>">
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