<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent" layoutH="70"> 
 
   <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'wishspecialorderimporttracenumberadd-grid',
    	/* 'enableAjaxValidation' => false,
    	'enableClientValidation' => true, */
    	'clientOptions' => array(
    			/* 'validateOnSubmit' => true,
    			'validateOnChange' => true,
    			'validateOnType' => false,
    			'afterValidate'=>'js:afterValidate',
    			'additionValidate'=>'js:checkResult', */
    	),
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(
         	'enctype'		=>'multipart/form-data',
			'onsubmit'=>"return iframeCallback(this);",
        	'novalidate'		=>'novalidate',
        	'class'		=>'pageForm required-validate',
        	'is_dialog'		=>1,
        	'target'	=> 'dialog',
        	//'onsubmit'	=>	'return validateCallback(this, dialogAjaxDone)'
        )
    ));
?>  
<div class="pageFormContent" layoutH="100">
<table class="dataintable_inquire" width="500px" cellspacing="1" cellpadding="3" border="0">
    <tbody>
    
    	<tr>
        	<td>模板文件</td>
        	<td>
        		<a href="/uploads/wish/wish追踪号导入模板.xls" target="__blank">下载模板</a>
        	</td>
        </tr>
        
    	<tr>
        	<td>选择文件（xlsx格式）</td>
        	<td>
        		<input type="file" name="csvfilename" />
        	</td>
        </tr>

        
    	<tr>
            <td width="15%" style="font-weight:bold;">
            	<span><?php echo Yii::t('wish_order', 'Ship Code');?></span>
            </td>
            <td>
        		<select name="ship_code" id="ship_code">
        			<option value=""><?php echo Yii::t('system', 'Please Select');?></option>
        			<?php foreach (UebModel::model('WishSpecialOrderShipCode')->getShipCodesPairs() as $code=>$ship):?>
        			<option value="<?php echo $code;?>"><?php echo $ship;?></option>
        			<?php endforeach;?>
        		</select>
            </td>
        </tr>
    </tbody>
</table>

    <div class="formBar" style="width: 500px;">
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