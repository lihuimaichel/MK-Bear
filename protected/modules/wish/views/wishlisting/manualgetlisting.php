<style>
.pageFormContent #domestic label, .pageFormContent #international label{
	width:auto;
}

.pageFormContent #domestic select,.pageFormContent #international select {
    float: none;
}

</style>
<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'getlistingid-grid',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl("/wish/wishlisting/manualgetlisting"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:auto;">
        	<div class="row">
        		<label for="manualListingSiteId">账号</label>        		
        		<select name="account_id" id="manualListingSiteId">
					<option value="">   请选择    </option>
					<?php foreach (WishAccount::getIdNamePairs() as $key=>$val):?>
					<option value="<?php echo $key;?>">   <?php echo $val;?>    </option>
					<?php endforeach;?>
					
				</select>    
                <div class="buttonActive" style="margin-left:50px;">
                    <div class="buttonContent"><a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)">拉取listing</a> </div>
                </div>     		
			</div>
        </div>
    </div>

    <?php $this->endWidget(); ?>
</div>

<script type="text/javascript">
//保存刊登数据
function saveInfo(){
	$.ajax({
		type: 'post',
		url: "<?php echo Yii::app()->createUrl('/wish/wishlisting/manualsavelisting');?>",
		data:$('form#getlistingid-grid').serialize(),
		success:function(result){
			if(result.statusCode != '200'){
				alertMsg.error(result.message);
			}else{
				alertMsg.correct(result.message);
			}
		},
		dataType:'json'
	});
}
</script>