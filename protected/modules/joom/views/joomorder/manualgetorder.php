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
        'id' => 'getorderid-grid',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        // 'action' => Yii::app()->createUrl("/joom/joomorder/manualgetorder"),
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
        		<label for="manualOrderSiteId">账号</label>        		
        		<select name="account_id" id="manualAccountId">
					<option value="">   请选择    </option>
					<?php foreach (JoomAccount::getIdNamePairs() as $key=>$val):?>
					<option value="<?php echo $key;?>">   <?php echo $val;?>    </option>
					<?php endforeach;?>
					
				</select>        		
			</div>
	        
	        <div class="row">
        		<label for="manualOrder">OrderIDS</label>        		
                <textarea name="order_id" id="manualOrder" cols="50" rows="6"></textarea>
                （joom平台上的订单ID，每个订单ID用半角逗号,隔开）
        	</div>
        </div>
		<div class="pd5" style="height:auto;">
			<div class="row" style="margin-left:200px;">
                <div class="buttonActive" style="margin-right:80px;">
                    <div class="buttonContent"><a class="saveBtn" onClick="saveOrderInfo(1);" href="javascript:void(0)">拉取订单</a> </div>
                </div> 
                <div class="buttonActive">
                    <div class="buttonContent"><a class="saveBtn" onClick="saveOrderInfo(2);" href="javascript:void(0)">拉取并同步oms</a></div>
                </div>
                <input type="hidden" value="1" id="manual_type_id" name="type_id">
            </div>
        </div>
    </div>

    <?php $this->endWidget(); ?>
</div>

<script type="text/javascript">
//保存刊登数据
function saveOrderInfo(type_id){
    if(!type_id){
        alertMsg.error("错误");
        return ;
    }
    // $("#manual_type_id").val(type_id);//赋值类型
    var account_id = $("#manualAccountId").val();
    if(account_id == ''){
        alertMsg.error('账号名称不能为空');
        return false;
    }

    var orderIds = $("#manualOrder").val();
    if(orderIds == ''){
        alertMsg.error('订单ID不能为空');
        return false;
    }
    var postData = {'account_id':account_id, 'order_id':orderIds,  'type_id':type_id};
	$.ajax({
		type: 'post',
		url: "<?php echo Yii::app()->createUrl('/joom/joomorder/manualsaveorder');?>",
		data:postData,
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