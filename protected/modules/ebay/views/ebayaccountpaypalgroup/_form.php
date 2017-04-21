<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'groupRuleForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
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
	            <?php echo $form->labelEx($model, 'group_name'); ?>
	            <?php echo $form->textField($model, 'group_name',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'group_name'); ?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'status');?>
        		<?php echo $form->dropDownList($model, 'status', EbayAccountPaypalGroup::getStatusOptions());?>
        		<?php echo $form->error($model,'status');?>
        	</div>
        	<div class="row">
        		<input type='button' onClick='addpaypalrule();' value='规则添加'/>
        		<span>说明：最后结束金额需要大于999999</span>
        	</div>
        	<div class="row" id="group_rule">
	            	
			</div>
        	
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <!-- <button type="submit"><?php echo Yii::t('system', 'Save')?></button> -->                     
                        <button type="button" onClick='check_submit(0);'><?php echo Yii::t('system', 'Save')?></button>                     
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
var paypalList = <?php echo json_encode($paypalList);?>;
//获取paypal
function getPaypal(paypal_id,service_num){
	var paypal_id = paypal_id ? paypal_id : '';
	var html = "<select name='account_paypal["+service_num+"]' class='account_paypal'>";
	html += "<option value=''>请选择</option>";
	$.each(paypalList,function(key,val){
		html += "<option value='"+key+"' "+(paypal_id==key?'selected':'')+">"+val+"</option>";
	});
	html += "</select>";
	return html;
}

//添加
var servicelNum = 0;
function addpaypalrule(paypal_id,money_start,money_end){
	var paypal_id = paypal_id ? paypal_id : '';
	var money_start = money_start ? money_start : '';
	var money_end = money_end ? money_end : '';
	var max = 5;
	
	if($("#group_rule table").size()>=max){
		alert("分组不能超过"+max+"个");
		return;
	}
	servicelNum++;

	var html = "<table style='width:460px;margin-bottom:15px;border:1px solid #ccc;padding:5px;' class='tb_trbl'>"
		+"<tr><td>paypal帐号:</td><td>"+getPaypal(paypal_id,servicelNum)+"</td></tr>"
		+"<tr><td><span>开始金额:</td><td></span><input name='amount_start["+servicelNum+"]' type='text' class='amount_start' value='"+money_start+"'></td></tr>"
		+"<tr><td><span>结束金额:</td><td></span><input name='amount_end["+servicelNum+"]' type='text' class='amount_end' value='"+money_end+"'></td></tr>"
		+"<tr><td><span><input type='button' onClick='removepaypalrule(this);' value='删除'/></span></td><td></td></tr>"
		+"</table>";
	$("#group_rule").append(html);
}
//删除
function removepaypalrule(obj){
	$(obj).parents('.tb_trbl').remove();
}

function check_submit(amount){
	if(!amount){amount = 0;}
	var thisTable = $("#group_rule table").eq(amount);
	if(thisTable.length==0){
		alert("请添加规则");
		return false;
	}
	if($.trim(thisTable.find('.amount_start').val())-0>0.01){
		alert("第一个开始值为0.01");
		return false;
	}
	
	var flag = true;
	do{
		var account_paypal = thisTable.find('.account_paypal').val();
		var start_amount = $.trim(thisTable.find('.amount_start').val())-0;
		var end_amount = $.trim(thisTable.find('.amount_end').val())-0;
		if(account_paypal==''){
			alert("请选择paypal帐号");
			return false;
		}
		if(start_amount<=0 || end_amount<=0){
			alert("开始金额或结束金额不能小于0");
			return false;
		}
		if(start_amount>=end_amount){
			alert("开始金额应小于结束金额");
			return false;
		}
		var nextTable = thisTable.next();
		if(nextTable.length>0){
			var res = amount_rule(thisTable,nextTable);
			if(res==false){
				alert("金钱范围要衔接上");
				return false;
			}else{
				thisTable = res;
			}
		}else{
			//最后一个值必须大于1000000
			if(end_amount<999999){
				alert("最后结束金额必须大于等于999999");
				return false;
			}
			flag = false;
		}	
	}while(flag)
	//提交
	$('#groupRuleForm').submit();
}
//规则判断
function amount_rule(obj,nextObj){
	var start = obj.find('.amount_end').val()-0;//-0转为整型
	var end = nextObj.find('.amount_start').val()-0;
	if(start.toFixed(2)!=end.toFixed(2)){//如果没有衔接上提示错误
		return false;
	}
	return nextObj;
}

<?php 
if($paypalRule){
	foreach ($paypalRule as $rule){
		echo "addpaypalrule('{$rule['paypal_id']}','{$rule['amount_start']}','{$rule['amount_end']}');";
	}
}
?>
</script>