<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
Yii::app()->clientscript->registerScriptFile('/js/autocomplete/jquery.autocomplete.pack.js');
Yii::app()->clientscript->registerCssFile('/js/autocomplete/jquery.autocomplete.css');
$form = $this->beginWidget('ActiveForm', array(
    'id' => 'ConditionsRulesForm',
    'enableAjaxValidation' => false,
    'enableClientValidation' => true,
    'focus' => array($model, ''),
    'clientOptions' => array(
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnType' => false,
        'afterValidate' => 'js:afterValidate',
    	//'additionValidate' => 'js:checkOption',
    ),
    'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
    'htmlOptions' => array(
        'class' => 'pageForm',
    )
));

$ruleClassList = TemplateRulesBase::getRuleClassList();

?>

<div class="pageFormContent" layoutH="55">
	<div class="left" style="width:520px;float:left;">
		<!-- 选择平台 start -->
		<div class="bg14 pdtb2 dot" style="height:18px;padding-top:6px;font-weight:bold;">
			选择平台
		</div>
		<div class="dot7 ifclicked" id="platform_code" style="width:497px;background-color:white;padding:10px;vertical-align:top;height:30px;line-height:100%;border:1px green dashed;overflow-y:scroll;">
			<ul id="ul2">
				<li>
		    		<?php echo $form->labelEx($model,'platform_code'); ?>
	        		<?php echo $form->dropDownList($model,'platform_code',UebModel::model('platform')->getPlatformList(), array('class'=>'combox','empty'=>'%')); ?>
	        		<?php echo $form->error($model,'platform_code'); ?>
        		</li>
	    	</ul>
	    </div>
	    <br/>
	
		<!-- 已选条件 start -->
		<div class="bg14 pdtb2 dot" style="height:18px;padding-top:6px;font-weight:bold;">
			已选条件
		</div>
		<div class="dot7 ifclicked" id="rule_list" style="width:497px;background-color:white;padding:10px;vertical-align:top;height:200px;line-height:180%;border:1px green dashed;overflow-y:scroll;">
			<div id='div_select_content'></div>
	    </div>
	    <br/>
	    <!-- 设定动作 start -->
	    <div class="bg14 pdtb2 dot" style="height:18px;padding-top:6px;font-weight:bold;">设定处理动作</div>
	    <div class="dot7" style="width:498px;padding:10px;vertical-align:top;height:40px;background-color:white;border:1px green dashed;overflow-y:scroll;">
	    	<ul id="ul3">
	    		<?php /* foreach ($ruleClassList as $key => $value) {
	    			echo '<li>';
	    			echo CHtml::radioButton('input_sel_action','',array('value'=>$key)).' '.$value;
	    			echo '</li>';
	    		}  */?>
	    		<li>
	    		<?php echo $form->radioButtonList( $model,'rule_class',$ruleClassList,array('separator'=>'<li>','labelOptions'=>array('class'=>'radiolabel')) ); ?>
	    		</li>
	    	</ul>
	    </div>
	    <br/>
	    <!-- 规则设置 start -->
	    <div class="bg14 pdtb2 dot" style="height:18px;padding-top:6px;font-weight:bold;">规则设置</div>
	    <div class="dot7" style="width:498px;padding:10px;vertical-align:top;height:180px;background-color:white;border:1px green dashed;overflow-y:scroll;">
	    	<ul id="ul2">
	    		<?php echo $form->hiddenField($model, 'template_name', array('readonly' => true, 'rel' => "{type:'input', callback:'providerCodeCallbacks'}")); ?>
            	<?php echo $form->hiddenField($model, 'template_id', array( 'size' => 8)); ?>
	    		<li>
	    			<?php echo $form->labelEx($model, 'template_name'); ?>
	    			<div class="chosen-container chosen-container-multi" style="width:130px;float:left;" title="" id="Inquire_provider_code_chosen">
						<ul class="chosen-choices" id='show-con'>
						<?php if (isset($model->template_name) && !empty($model->template_name) ):
							$arr = explode(',',$model->template_name);
							foreach($arr as $val):
							?>
								<li class="search-choice"><span><?php echo $val;?></span>
								<a onclick="delCodes(this)"; class="search-choice-close" data-option-array-index="<?php echo $val;?>"></a></li>
							<?php 
							endforeach;
						endif;?>
						<li style="margin: 3px 0 3px 5px;padding: 3px 20px 3px 5px;"><span>&nbsp;</span><a data-option-array-index="0"></a></li>
						</ul>
					</div>           
	    			&nbsp;&nbsp;<a id="a_set_return_content">Select</a>
	    		</li>
	    		<li>
	    			<?php echo $form->labelEx($model,'priority'); ?>
	    			<?php echo $form->textField($model,'priority',array('size'=>10)); ?>
	    		</li>
	    		<li>
	    			<?php echo $form->labelEx($model,'rule_name'); ?>
	    			<?php echo $form->textField($model,'rule_name',array('size'=>60)); ?>
	    			<?php echo $form->error($model,'rule_name'); ?>
	    		</li>
	    		<li>
	    			<?php echo $form->labelEx($model,'is_enable'); ?>
        			<?php echo $form->dropDownList($model,'is_enable',$model->getIsEnableArr(), array('class'=>'combox')); ?>
        			<?php echo $form->error($model,'is_enable'); ?>
	    		</li>
	    	</ul>
	    </div>
	    <br/>
	</div>
	
	<div class="center" style="width:10px;float:left;">&nbsp;</div>
	
	<div class="right" style="float:left;width:240px;height:auto;">
		<!-- 可选条件 start -->
	    <div class="bg14 pdtb2 dot" style="width:230px;height:18px;padding-top:6px;font-weight:bold;">
			可选条件
		</div>
		<div class="dot7 iflist" style="width:220px;background-color:white;padding:10px;vertical-align:top; height:510px;border:1px green solid">
			<div id="div_can_select_res" style="padding:0 6px;height:490px;border:0px dashed green;overflow-y:auto;">
				<div id="div_can_select_res_ty"></div>
				<div style="height:10px;"></div>
				<div id="div_can_select_res_zy">请先设定"处理动作"</div>
			</div>
			
			<input type="checkbox" id="input_show_res_ty" checked="checked">显示通用条件
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
            <div class="button">
                <div class="buttonContent">
                    <button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button>
                </div>
            </div>
    	</li>
	</ul>
</div>

<?php $this->endWidget(); ?>


<script type="text/javascript">

var url2 = 'common/conditionsrules/';
$().ready(function(){ 
	
	$('#input_show_res_ty').click(function(){
		$('#div_can_select_res_ty').hide();
		if($(this).attr('checked') == 'checked') {
			$('#div_can_select_res_ty').show();
		}
	});
	
	$.get(url2+'getcanselectres',{c:'%'},function(data){
		$('#div_can_select_res_ty').html(data);
	});
	
	$("input:radio[name='ConditionsRules[rule_class]']").click(function(){
		var platform_code = $('#ConditionsRules_platform_code').val();
		if(platform_code == ''){
			$(this).attr('checked',false);
			alert('请先选择平台类型！');
		}else{
			$.get(url2+'getcanselectres',{c:$(this).val(),p:platform_code},function(data){
				$('#div_can_select_res_zy').html(data);
			});
		}
	});

	var id = <?php echo isset($_REQUEST['id']) ? $_REQUEST['id'] : 0; ?>;
	if(id>0) {
		var platform_code = $('#ConditionsRules_platform_code').val();
		var cid = 0;
		$('input:radio[name="ConditionsRules[rule_class]"]').each(function(){
			if($(this).val() == <?php echo $model->rule_class; ?>) {
				$(this).attr('checked','checked');
				cid = $(this).val();
			}
		});
		
		$.get(url2+'getcanselectres',{c:'%',id:id,p:platform_code},function(data){
			$('#div_can_select_res_ty').html(data);
		});
		$.get(url2+'getcanselectres',{c:cid,id:id,p:platform_code},function(data){
			$('#div_can_select_res_zy').html(data);
		});

	}

	$('#a_show_msg1').click(function(){
		var url='/common/conditionsrules/showexplain/type/1/target/dialog';
		$.pdialog.open(url, '', '说明', {width: 350, height: 200,mask:true});
	});

	$('#a_set_return_content').click(function(){
		var action_id = $('input:radio[name="ConditionsRules[rule_class]"]:checked').val();
		var platform_code = $('#ConditionsRules_platform_code').val();
		var url='/common/conditionsrules/settemplate/action_id/'+action_id+'/platform_code/'+platform_code;
		
		if(action_id == ''){
			alert('暂不能匹配选择的模板');
			return;
		}else{
			$.get(url,{action_id:action_id,platform_code:platform_code},function(data){
				$.pdialog.open(decodeURIComponent(data.url), 'set_return_content', '查找'+data.tab_title, {width: 800, height: 450, mask:true});
			},'json');
		}
		
	});
	
});


function providerCodeCallbacks(data) {
	var data = eval("("+data+")");
	if(data==''){
		alert('模板不能为空！');
    	return false;
	}
	
	var code = '';
	var id = '';
	var str =[];
	
	$.each(data,function(i,item){
		code += item.tpl_name+',';
		id += item.id+',';
		str.push(item.tpl_name);
	});
	
	code = code.substring(0,code.length-1);
	id = id.substring(0,id.length-1);
	if(code.indexOf(',') >0){
		alert('模板只能选择一个！');
    	return false;
	}
	
	$('#ConditionsRules_template_id').val(id);
    $('#ConditionsRules_template_name').val(code);
    var string= '';
    $.each(str, function(i,val){
	   string += '<li class="search-choice"><span>'+val+'</span>';
	   string += '<a onclick=delCodes(this); class="search-choice-close" data-option-array-index="'+val+'"></a></li>';
	    });
	string += '<li style="margin: 3px 0 3px 5px;padding: 3px 20px 3px 5px;"><span>&nbsp;</span><a data-option-array-index="0"></a></li>';
	$('#show-con').html(string);
}

function delCodes(obj){
	$('#ConditionsRules_template_id').val('0');
	var curcode = $(obj).prev().text();
	var allcode = $('#ConditionsRules_template_name').val();
	var newcode = '';
	if(allcode.indexOf(','+curcode) >0){
	   newcode = allcode.replace(","+curcode,"");
	}else{
		if(curcode!=allcode){
			newcode = allcode.replace(curcode+",","");
	    }
	}
	$('#ConditionsRules_template_name').val(newcode);
    $(obj).parent().remove();
    if(newcode==''){
    	return false;
	}
}

</script>

<style>
#ul2 {border:0px red solid;margin:0px;padding:0px;}
#ul2 li {margin:0; padding:0; float: left;width:98%;}
#ul2 label {border:0px red solid;width:60px;}

#ul3 {border:0px red solid;margin:0px;padding:0px;}
#ul3 li {margin:0; padding:0; float: left;width:120px;}

#ul3 .radiolabel{display:inline;vertical-align: middle;float:none;}
</style>
