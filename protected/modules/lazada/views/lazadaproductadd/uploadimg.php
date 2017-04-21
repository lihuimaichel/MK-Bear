<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent" layoutH="70"> 
 
   <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'orderForm-grid',
         'clientOptions' => array(
        ),
    	
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(
         	'enctype'		=>'multipart/form-data',
			'onsubmit'=>"return iframeCallback(this);",
        	'novalidate'		=>'novalidate',
        	'class'		=>'pageForm required-validate',
        	'is_dialog'		=>1,
        )
    ));
?>  
<div class="pageFormContent" >
<table class="dataintable_inquire" width="800px" cellspacing="1" cellpadding="3" border="0">
    <tbody>
    	<tr>
        	<td>选择文件（xlsx格式）</td>
        	<td>
        		<input type="file" name="csvfilename" />
        	</td>
        </tr>
        <tr>
            <td width="15%" style="font-weight:bold;">
            	刊登类型：
            </td>
            <td>
				<input type="radio" name="is_check_add" value="1" checked>从待刊登列表刊登 
				<input type="radio" name="is_check_add" value="0" >从产品库
				<input type="radio" name="is_check_add" value="2" >删除原来重新传图
                <input type="radio" name="is_check_add" value="3" >产品库上传失败SKU重新传图
            </td>
        </tr>
        
    	<tr>
            <td width="15%" style="font-weight:bold;">
            	可用账号：
            </td>
            <td>
				<?php if($accounts):?>
				<select name="account_id">
					<option value="">
							选择账号
					</option>
					<?php foreach ($accounts as $id=>$account):?>
					<option value="<?php echo $id;?>">
							<?php echo $account;?>
					</option>
					<?php endforeach;?>
				</select>
				<?php else:?>
				No result!
				<?php endif;?>
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