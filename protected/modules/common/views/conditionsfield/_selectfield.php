
<div class="pageContent"> 
	<div class="pageFormContent" layoutH="95">
		<div class="row">
			<?php echo CHtml::dropDownList('A1','A1',TemplateRulesBase::getModelList(),array('empty'=>Yii::t('system','Please Select'),'onchange'=>'showFields(this)')); ?>
        </div>
		<div class="row">
			<?php echo CHtml::dropDownList('A2','A2',array()); ?>
        </div>
	</div>
	<div>
		<div class="row">
        	<?php echo CHtml::htmlButton('OK', array('id'=>'B1','onClick'=>'selectOK()')); ?>
        </div>
	</div>
</div>

<script type="text/javascript">

function showFields(obj) {
	$.getJSON('common/conditionsfield/getfields/tab/'+$(obj).val(), function(data){ 
		$("#A2").empty();
		$.each(data,function(i,item){
			$("<option></option>").val(item).text(i+' -> '+item).appendTo($("#A2"));
		});
	});
}

function selectOK() {
	$('#ConditionsField_field_name').val($('#A1').val()+'.'+$('#A2').val());
	$.pdialog.closeCurrent();
}
            
</script>