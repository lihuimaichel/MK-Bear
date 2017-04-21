<style type="text/css">
div.row {
	padding:0 15px;
	margin:10px auto;
}
div.row .title {
	font-size:22px;
	font-weight:bold;
	margin-bottom:15px;
}
.contents ul {
	margin:0;
	padding:0;
	overflow:hidden;
	list-style:none;
}
.contents ul li {
	display:block;
	float:left;
	margin:0 10px 0 0;
	width:180px;
}
.contents ul li label {
	float:none;
	display:inline;
}
</style>
<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent">   
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'offline-import',
//         'enableAjaxValidation' => false,
//         'enableClientValidation' => true,
         'clientOptions' => array(
//             'validateOnSubmit' => true,
//             'validateOnChange' => true,
//              'validateOnType' => false,
//              'afterValidate'=>'js:afterValidate',
//          		'callbackframe'		=>'aa();',
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
    <div class="pageFormContent" layoutH="80">  
          <div class="row">
                    <div class="title"><?php echo Yii::t('aliexpress_product', 'STEP ONE Import CSV File');?></div>
                    <?php echo CHtml::fileField('offline_file', '');?>
         </div> 
         <div class="row">
         	<div class="title"><?php echo Yii::t('aliexpress_product', 'STEP TWO Choose Account');?></div>
         	<div class="contents">
         		<ul>
		          	<?php foreach ($account_list as $row) { ?>
		          	<li>
		         	<input id="account_id_<?php echo $row['id'];?>" type="checkbox" name="account_id[]" value="<?php echo $row['id'];?>" /><label for="account_id_<?php echo $row['id'];?>"><?php echo $row['short_name'] ;?></label>
		         	</li>
		         	<?php } ?>
         		</ul>
         	</div>
         </div> 
	</div>
 	<div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                    	<button type="submit"><?php echo Yii::t('system', 'Save') ?></button>
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
<script type="text/javascript">
//保存刊登数据
$(function(){
	$('#select_all').change(function(){
		var obj = this;
		$('input[name=account_id\\[\\]]').each(function(){
			if (obj.checked)
				this.checked = true;
			else
				this.checked = false;
		});
	});
});
</script>