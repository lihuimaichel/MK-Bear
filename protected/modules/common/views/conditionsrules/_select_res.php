
<ul id="ul_res_list">
<?php 
if ($ruleClass) {
	$color = '#CBD5FC';
	$msg = '无专用条件';
}else {
	$color = '#FFC7AC';
	$msg = '无通用条件';
}

if ($conditionsFieldList) {
	foreach ($conditionsFieldList as $key => $value) {
		$value['is_checked'] = isset($value['is_checked'])?$value['is_checked']:0;
		$value['detail_id'] = isset($value['detail_id'])?$value['detail_id']:0;
		echo '<li>';
		echo CHtml::checkBox('input_sel_field',$value['is_checked'],array('value'=>$value['id'],'id'=>'fid_'.$value['id'],'d_id'=>json_encode($value['detail_id']))).' '.$value['field_title'];
		echo '</li>';
	} 
}else {
	echo $msg;
}
?>
</ul>

<style>
#ul_res_list {border:0px solid green;}
#ul_res_list li {margin-bottom:5px;background-color:<?php echo $color; ?>;border:0px;}
</style>

<script type="text/javascript">

$('input:checkbox[name=input_sel_field]').click(function(){
	var fid = $(this).val();
	var fsta = 0;
	if ($(this).attr('checked') == 'checked') {
		fsta = 1;
		$.get(url2+'getselectcontent',{fid:fid,fsta:fsta},function(data){
			if($('#div_ok_res_fid_'+fid).length < 1) {
				
				$('#div_select_content').append(data);
			}
		});
	}else{
		$('#div_ok_res_fid_'+fid).remove();
	}
	
});

$('input:checkbox[name=input_sel_field]').each(function(){
	var fid = $(this).val();
	var d_id = $(this).attr('d_id');
	if ($(this).attr('checked') == 'checked') {
		$.get(url2+'getselectcontent',{fid:fid,fsta:1,d_id:d_id},function(data){
			//alert(data);
			if($('#div_ok_res_fid_'+fid).length < 1) {
				$('#div_select_content').append(data);
			}
		});
	}
});


</script>