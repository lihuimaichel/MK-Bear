<?php $fid = $ruleFieldInfo['id']; 
?>

<div class="pageContent"> 
	<div class="pageFormContent" layoutH="95">
		<div class="row">
			请设置 " <?php echo $ruleFieldInfo['field_title']; ?>"
			<font color='red' id="err_msg" style="margin-left:20px;"></font>
			<br/><br/>
			<font color='red'><?php echo $ruleFieldInfo['input_msg_content']; ?></font>
        </div>
        <form action="" name="form_set_content" id="form_set_content">
		<div class="row" id="div_set_content_a1">
			<!-- AAA -->
        </div>
        </form>
        <div style="display:none;">
        	<span id="cur_fid"><?php echo $fid; ?></span>
        	<div id="hide_ul_aa">
        	<ul class="ul_a1" id="{ul_aa}">
				<li>
					<span class="sp_a1"></span>
					<span class="sp_a1">
					<?php echo CHtml::dropDownList('{cal_aa}','{cal_aa_v}',TemplateRulesBase::getCalConditionList(),array('empty'=>Yii::t('system','Please Select'),'onchange'=>'')); ?>
					</span>
					
					<span class="sp_a1">   </span>
					<span class="sp_a1">
					<?php echo CHtml::textArea('{cont_aa}', '{cont_aa_v}', array('rows'=>2,'cols'=>'40')); ?>
					</span>
					
					<span class="sp_a1">   </span>
					<span class="sp_a1">
					<?php if($ruleFieldInfo['field_type'] == ConditionsField::FIELD_TYPE_LIST && $ruleFieldInfo['auto_complete'] == 1): ?>
						<?php echo CHtml::textField('{auto_text_aa}', '', array('onclick'=>'autobuquan({func_aa},\''.$ruleFieldInfo['auto_complete_field'].'\')','placeholder'=>'输入搜索...')); ?>
						<?php echo CHtml::hiddenField('{hid_count_aa}', '', array()); ?>
					<?php elseif($ruleFieldInfo['field_type'] == ConditionsField::FIELD_TYPE_LIST && $ruleFieldInfo['auto_complete'] != 1): ?>
						<?php echo CHtml::dropDownList('{unauto_text_aa}','',$dropDownList,array('empty'=>Yii::t('system','Please Select'),'onchange'=>'unautochange(this)')); ?>
					<?php endif; ?>
					</span>
					
					<?php if ($ruleFieldInfo['is_unit']): ?>
					<span class="sp_a1">   </span>
					<span class="sp_a1">
					<?php echo CHtml::dropDownList('{unit_aa}','{unit_aa_v}',$ruleFieldInfo['unit_code_data'],array('empty'=>Yii::t('system','Please Select'),'onchange'=>'')); ?>	
					</span>
					<?php endif; ?>
					
				</li>
			</ul>
			
					
        	</div>
        </div>
	</div>
	<div>
		<div class="row">
			<?php if ($ruleFieldInfo['field_extends']): ?>
					<?php foreach( $ruleFieldInfo['field_extends'] as $key => $value ): ?>
					<span class="sp_a1">   </span>
					<span class="sp_a1">
					<?php if( isset($selectedArr[$key]) ):?>
					<?php echo CHtml::dropDownList($key,$selectedArr[$key],$value,array('empty'=>'选择范围','onchange'=>'setFiedExtend("'.$key.'",this.value)')); ?>
					<?php else:?>
					<?php echo CHtml::dropDownList($key,'',$value,array('empty'=>'选择范围','onchange'=>'setFiedExtend("'.$key.'",this.value)')); ?>
					<?php endif;?>
					</span>
					<?php endforeach;?>
					<?php endif; ?>
			<span class="sp_a2"><?php echo CHtml::htmlButton('+', array('id'=>'','onClick'=>'addCalUl()')); ?></span>
			<span class="sp_a2"><?php echo CHtml::htmlButton('-', array('id'=>'','onClick'=>'removeCalUl()')); ?></span>
        	<span class="sp_a2"><?php echo CHtml::htmlButton('OK', array('id'=>'','onClick'=>'selectOK()')); ?></span>
        </div>
	</div>
</div>

<?php //var_dump($ruleFieldInfo); ?>

<script type="text/javascript">

var res_c_fid = $('#cur_fid').html();

function autobuquan(aid,auto_complete_field){
	var curr_count = $('#hid_count_'+res_c_fid+'_'+aid).val();
	if( curr_count == 0 ){
		$('#hid_count_'+res_c_fid+'_'+aid).val(1);
		
		$.getJSON('/common/conditionsrules/get'+auto_complete_field+'/fid/'+res_c_fid,function(data) {
			
			$('#auto_text_'+res_c_fid+'_'+aid).autocomplete(data,{
				 max: 12,    //列表里的条目数
				 minChars: 1,    //自动完成激活之前填入的最小字符
				 width: 350,     //提示的宽度，溢出隐藏
				 scrollHeight: 300,   //提示的高度，溢出显示滚动条
				 matchContains: true,    //包含匹配，就是data参数里的数据，是否只要包含文本框里的数据就显示
				 autoFill: false,    //自动填充
				 formatItem: function(row, i, max) {
					 return row.auto_y + ' ' + row.auto_x;
				 },
				 formatMatch: function(row, i, max) {
					 return row.auto_y + row.auto_x;
				 },
				 formatResult: function(row) {
					 return row.auto_y;
				 }
			 }).result(function(event, row, formatted) {
				 var old_cont_val = $('#cont_'+res_c_fid+'_'+aid).val();
				 if( old_cont_val != '' ){
					 $('#cont_'+res_c_fid+'_'+aid).val(old_cont_val+'{##}'+row.auto_x);
				 }else{
					 $('#cont_'+res_c_fid+'_'+aid).val(row.auto_x);
				 }
				 
			 });
			
		 });
		
	}
	
}

var ok_res_fid_cond = $('#ok_res_fid_'+res_c_fid+'_cond').val();
if(ok_res_fid_cond.length) {
	var obj = eval(ok_res_fid_cond);
	for(var i=0;i<obj.length;i++) {
		ul_html = strHtmlReplace(i,obj[i].cal,obj[i].cont);
		$('#div_set_content_a1').append(ul_html);
		$('#cal_'+res_c_fid+'_'+i).val(obj[i].cal);
		$('#unit_code_'+res_c_fid+'_'+i).val(obj[i].unit_val);
	}
}else{
	var l_i = 0;
	ul_html = strHtmlReplace(l_i,'','<?php echo $ruleFieldInfo['field_default_value']; ?>');
	$('#div_set_content_a1').append(ul_html);
}

function addCalUl() {
	var l_i = 0;
	var l_ul_id = $('#div_set_content_a1 ul:last').attr('id');
	l_i = l_ul_id.replace('ul_'+res_c_fid+'_','');
	l_i = parseInt(l_i) + 1;
	ul_html = strHtmlReplace(l_i,'','');
	$('#div_set_content_a1').append(ul_html);
}

function removeCalUl() {
	var l_i = 0;
	var l_ul_id = $('#div_set_content_a1 ul:last').attr('id');
	l_i = l_ul_id.replace('ul_'+res_c_fid+'_','');
	if(parseInt(l_i) > 0) {
		$('#div_set_content_a1 ul:last').remove();
	}
}

//点击 OK
function selectOK() {
    var jsonuserinfo = $('#form_set_content').serializeObject();  
    var data = JSON.stringify(jsonuserinfo);
    
	$.post(url2+'responseselectcontent',{fid:res_c_fid,data:data},function(data){
		if(data == '-100') { $('#err_msg').html('有请选择项！'); }
		else if(data == '-200') { $('#err_msg').html('有未填项！'); }
		else {
			$('#ok_res_fid_'+res_c_fid+'_cond').val(data);
			$.pdialog.close('res_content');
		}
	});
}

function strHtmlReplace(aid,v1,v2) {
	ul_html = $('#hide_ul_aa').html().replace('{ul_aa}','ul_'+res_c_fid+'_'+aid).replace(new RegExp('{cal_aa}','g'),'cal_'+res_c_fid+'_'+aid).replace(new RegExp('{cal_aa_v}','g'),v1).replace(new RegExp('{cont_aa}','g'),'cont_'+res_c_fid+'_'+aid).replace(new RegExp('{cont_aa_v}','g'),v2);
	ul_html = ul_html.replace(new RegExp('{unit_aa}','g'),'unit_code_'+res_c_fid+'_'+aid).replace(new RegExp('{unit_aa}','g'),'unit_code_'+res_c_fid+'_'+aid);
	ul_html = ul_html.replace( new RegExp('{auto_text_aa}','g'),'auto_text_'+res_c_fid+'_'+aid );
	ul_html = ul_html.replace( new RegExp('{func_aa}','g'),aid );
	ul_html = ul_html.replace( new RegExp('{hid_count_aa}','g'),'hid_count_'+res_c_fid+'_'+aid );
	return ul_html;
}

$.fn.serializeObject = function() {    
   var o = {};    
   var a = this.serializeArray();    
   $.each(a, function() {    
       if (o[this.name]) {    
           if (!o[this.name].push) {    
               o[this.name] = [o[this.name]];    
           }    
           o[this.name].push(this.value || '');    
       } else {    
           o[this.name] = this.value || '';    
       }    
   });    
   return o;
}; 

function setFiedExtend( field_extend,obj ){
	var oldInputHtml = $('#'+field_extend+'_'+res_c_fid);
	if( oldInputHtml.length != 0 ){
		oldInputHtml.val(obj);
	}else{
		var inputHtml = "<input id='"+field_extend+"_"+res_c_fid+"' alt='"+field_extend+"' name='"+field_extend+"["+res_c_fid+"]' type='hidden' value='"+obj+"' />";
		$('#div_ok_res_fid_'+res_c_fid).append(inputHtml);
	}
}

</script>

<style>
.row {height:50px;}
.ul_a1 {width:700px; margin-left:40px;border:0px dashed green;height:50px;}
.ul_a1 li {margin-top:10px;}
.sp_a1 {float:left;border:0px solid red;margin-right:10px;valign:bottom;}
.sp_a2 {margin-left:30px;}
</style>