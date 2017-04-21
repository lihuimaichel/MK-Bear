<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpressaccountbindcategorylist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=categoryId_]").aliexpressCategoryListAjax();
    var first_category = null;
    var second_category = null;
    var second_category_all_status = false;
    var second_category_not_check_nums = 0;
    var first_category_array = '';
    var second_category_array = ',';
    var third_category_array = ',';
    function update_first_category(obj){
        if (obj.val() != first_category){ 
        	console.log(obj.is(':checked'));       	
        	if (obj.is(':checked')) obj.prop('checked',false); else obj.prop('checked',true);
        	alertMsg.error('请先点击这个一级分类，再选择这个复选框！');       	
        	return false;
        }
        if (obj.is(':checked')){
        	var category_id = obj.val();
        	second_category_all_status = true;
        	$('.bind_level_2').find('input[id^=checkbox_level_]').each(function(){
            	$(this).prop('checked',true);
            	var level_2_value = $(this).val();
            	//console.log(level_2_value);
            	second_category_array = second_category_array.replace("," + level_2_value + ",",',');
            	//console.log(second_category_array);
            	second_category_array += level_2_value + ",";
            	//console.log('level_2_value:'  + level_2_value);
            });
        } else {
        	second_category_all_status = false;
        	$('.bind_level_2').find('input[id^=checkbox_level_]').each(function(){
        		var level_2_value = $(this).val();
            	second_category_array = second_category_array.replace("," + level_2_value + ",",',');
            	$(this).prop('checked',false);
            });
        }
        console.log('first_category:' + first_category);       
        console.log('second_category_all_status:' + second_category_all_status);       
        console.log('first_category_array:' + first_category_array);       
        console.log('second_category_array:' + second_category_array);       
        console.log('third_category_array:'  + third_category_array);              
    }
</script>
<style type="text/css">
    ul.rightTools {float:right; display:block;}
    ul.rightTools li{float:left; display:block; margin-left:5px} 
    .setRate{padding-right:10px; line-height: 22px; cursor:pointer; float:right;}
    .updateCate{padding-left:10px; line-height: 22px; cursor:pointer; float:left;}
</style>
    <br /><br />&nbsp;&nbsp;&nbsp;
    <label for="EbayProductAttributeTemplate_site_id">请选择需要绑定分类的用户账号：</label>        		
    <select name="account_id" id="userIdJS" class="userIdClass" style="height: 28px; width:188px;">                   
					<option value="">&nbsp;请选择</option>
					<?php foreach (AliexpressAccount::getIdNamePairs() as $key=>$val):?>
					<?php 
                        if ($key == Yii::app()->request->getParam('account_id')) {
                            $selected = 'selected="selected"';
                        } else $selected = '';
                    ?>
					<option value="<?php echo $key;?>" <?php echo $selected; ?>>   <?php echo $val;?>    </option>
					<?php endforeach;?>
					
	</select>
	<br /><br />    		
<div class="pageContent" style="padding:5px;width:1800px;"> 
	<div layoutH="10" style="float:left; display:block; overflow:auto; width:380px; border:solid 1px #CCC; line-height:21px; background:#fff">
		<div class="panelBar">
			<ul class="toolBar">
				<li style="width:100%;">
				    <div class="updateCate" >一级分类</div>
				    <div class="setRate" onclick="">
				        <input type="button" value=" 保存设置   " onclick="insertOrUpdate()" />
				    </div>
				</li>          
			</ul>
		</div>
		<ul class="tree treeFolder" id="tree-menu" >
			<?php
				$str = '';
				foreach ($categoryList as $key => $val) {
					$commission = '';
					if($key != 36){
						$commission = AliexpressCategoryCommissionRate::getCommissionRateText($key);
					}
					$str .= "<li style='position:relative;'>";
					$str .= "<span>";
					$str .= "<input level=\"1\" type='checkbox' id='checkbox_{$key}' value='{$key}' onclick='update_first_category($(this))' style='position:absolute; right:6px; display:none;' />";
					$str .= '<a level="1" href="javascript::void(0);" id =categoryId_' . $key.' rel="'.$key.'">';					
					//$str .= $val.$commission;					
					$str .= $val;					
					$str .= '</a>';					
					$str .= '</span>';
					$str .= '</li>';
					
				}
				echo $str;
			?>
		</ul>
	</div>
<div id="aliexpressCategoryAccessPanel" class="unitBox" style="margin-left:246px;"></div>
</div>
<script type="text/javascript">
/**
 * 插入或则更新数据
 */
function insertOrUpdate(){
	var userID = $('#userIdJS').val();
	var secondCategory = "";
	//
	if (userID == undefined || userID == null || $.trim(userID) == ''){
		//alert('请选择用户！');
		alertMsg.error('请选择用户！');
		return false;
	}
	//	
	/*
	if (second_category_all_status){
		secondCategory = "all";
	} else {
		secondCategory = second_category_array;
	}
	*/
	secondCategory = second_category_array;
	//
	if (first_category == null){
		alertMsg.error('请选择要绑定的一级分类！');
		return false;
	}
	if (secondCategory == ''){
		alertMsg.error('请选择要绑定的二级分类！');
		return false;
	}
	first_category_array = '';
	$('input[id^=checkbox_]').each(function(){
		if ($(this).attr('level') == '1' && $(this).is(':checked')){
			first_category_array += $(this).val() + ',';
		}
	});
	$.ajax({
		type: 'post',
		url: '/aliexpress/aliexpress_account_bind_category/do_insert_or_update',
		data:{
			bindUserID:userID,
			bindFirstCategory:first_category_array,
			bindSecondCategory:secondCategory,
			bindThirdCategory:third_category_array
		},
		success:function(result){
			//alert(result);
			console.log(result);
			if (result != null && typeof result == 'object'){
				if (result.status == 'failure'){
					alertMsg.error(result.msg);
				} else if (result.status == 'success'){
					alertMsg.correct(result.msg);
				}
			} else {
				$('#errorBox').html(result).fadeIn(666);
			}
		},
		
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			 //alert(XMLHttpRequest.status);
			console.log(XMLHttpRequest.status);
			 if (XMLHttpRequest.status == 500){
				 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
			 } else if (XMLHttpRequest.status == 404){
				 alertMsg.error('请求的页面不存在，404错误！');
			 } else if (XMLHttpRequest.status == 200){
				 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
			 }
		},
		
		dataType:'json'
    });	
	console.log('first_category:' + first_category);       
    console.log('second_category_all_status:' + second_category_all_status);       
    console.log('second_category_not_check_nums:' + second_category_not_check_nums);       
    console.log('first_category_array:' + first_category_array);       
    console.log('second_category_array:' + second_category_array);       
    console.log('third_category_array:'  + third_category_array); 
}
			
/**
 * 设置栏目的佣金比例
 */
function setCommissionRate(){
    var categoryId = $('.pageContent div.selected a').attr("rel");
    if(isNaN(categoryId) == true){
        alertMsg.error('请选择类目');
        return false;
    }

    //判断是否是珠宝饰品及配件ID
    if(categoryId == 36){
    	alertMsg.error('珠宝饰品及配件请设置二级类目');
        return false;
    }

    var url ='/aliexpress/aliexpress_account_bind_category/setcommissionrate/categoryId/' + categoryId;
    $.pdialog.open(url, 'setCommissionRate', '设置栏目的佣金比例', {width:360, height:150});
}
</script>
<div style="position:absolute; top:18%; left:8%; width:68%; height:58%; background:#f6f6f6; z-index:666666; display:none; padding:28px; font-size:18px; line-height:28px;" 
     id="errorBox" 
     onClick="$(this).html(''); $(this).fadeOut(666);"
><div>

