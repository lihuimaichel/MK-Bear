<?php $fid = $ruleFieldInfo['id']; ?>
<div id="div_ok_res_fid_<?php echo $fid; ?>">
设置&nbsp;&nbsp;<a class="a1" onclick="openSetResPage(<?php echo $fid;?>,'<?php echo $fieldExtendStr;?>')"><?php echo $ruleFieldInfo['field_title']; ?></a>
	<div style="display: none;">
	<input type="inputtext" id="ok_res_fid_<?php echo $fid; ?>" name="sel_field_id[]" value="<?php echo $fid; ?>" />
	<textarea rows="1" cols="20" id="ok_res_fid_<?php echo $fid; ?>_cond" name="sel_field_content[]"><?php echo $ruleFieldInfo['field_content']; ?></textarea>
	</div>
	<?php if ($fieldExtendArr): ?>
	<?php foreach( $fieldExtendArr as $key => $value ): ?>
	<input id="<?php echo $value['extend_name'];?>_<?php echo $fid;?>" alt="<?php echo $value['extend_name'];?>" name="<?php echo $value['extend_name'];?>[<?php echo $fid;?>]" type='hidden' value="<?php echo $value['extend_value'];?>" />
	<?php endforeach;?>
	<?php endif; ?>
</div>

<style>
.a1 { color:green; text-decoration:none;}
.a1:hover { color:#F00; text-decoration:underline;}
.a1:active { color:#30F;}
</style>

<script type="text/javascript">

function openSetResPage(fid,extendstr){
	var tmpStr = '';
	var tmpExtend = $("#div_ok_res_fid_"+fid).find("input[type='hidden']");
	tmpExtend.each( function(index,obj){
		tmpStr += ','+$(obj).attr('alt')+'|'+$(obj).val();
	} );
	tmpStr = tmpStr.substr(1);
	if( extendstr == '' ) extendstr = tmpStr;
	var url='/common/conditionsrules/setrulerescontent/fid/'+fid+'/extendstr/'+extendstr+'/target/dialog';
	$.pdialog.open(url, 'res_content', '设置内容', {width: 800, height: 450, mask:true});
}

</script>