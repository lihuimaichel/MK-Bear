<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent"> 
 
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
<div class="pageFormContent" layoutH="70">
<table class="dataintable_inquire" width="100%" cellspacing="1" cellpadding="3" border="0">
    <tbody>
    	<tr>
        	<td>选择文件（csv格式）</td>
        	<td>
        		<input type="file" name="csvfilename" />
        	</td>
        </tr>
    	<tr>
            <td width="15%" style="font-weight:bold;">
            	可用账号：
            </td>
            <td>
				<?php if($accounts):?>
				<div><input type="checkbox" id="allSelect" /><label style="float:none;display:inline;" for="allSelect"><?php echo Yii::t('system', 'All Selected');?></label></div><br/>
				<ul class="accounts">
					<?php foreach ($accounts as $id=>$account):?>
					<li style="width:180px;float:left">
							<input type="checkbox" name="accounts[]" value="<?php echo $id;?>" />
							<a style="font-size:20px;text-decoration:underline;color:blue;" href="javascript:;"><?php echo $account;?></a>
					</li>
					<?php endforeach;?>
				</ul>
				<?php else:?>
				No result!
				<?php endif;?>
            </td>
        </tr>
    </tbody>
</table>

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
<script type="text/javascript">
$(function(){
	$("#allSelect").click(function(){
		$('ul.accounts').find('input').attr('checked', !!$(this).attr('checked'));
	});
});
</script>