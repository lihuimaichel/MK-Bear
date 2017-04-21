<?php
if($list){
	Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpressaccountbindcategorylist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=levelCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
    function update_second_category(obj){       
    	var category_id = obj.val();
        if (obj.val() != second_category){ 
        	console.log(obj.is(':checked'));       	
        	if (obj.is(':checked')) obj.prop('checked',false); else obj.prop('checked',true);
        	alertMsg.error('请先点击这个二级分类，再选择这个复选框！');       	
        	return false;
        }
        if (obj.is(':checked')){
            $('#checkbox_' + first_category).prop('checked',true);
        	second_category_not_check_nums--;       	
            second_category_array = second_category_array.replace("," + category_id+",",","); 
        	second_category_array += category_id+",","";
        	if ($('.bind_level_3').attr('parentCategoryID') == category_id) 
        	$('.bind_level_3').find('input[id^=checkbox_level_]').each(function(){
            	$(this).prop('checked',true);
            	var level_3_value = $(this).val();
            	third_category_array = third_category_array.replace("," + level_3_value + ',',',');
            	third_category_array += level_3_value + ',';
            	//console.log('level_3_value:' + level_3_value);
            });
        } else {
        	second_category_not_check_nums++;
        	second_category_all_status = false; 
        	second_category_array = second_category_array.replace("," + obj.val()+',',','); 
        	if ($('.bind_level_3').attr('parentCategoryID') == category_id)       	
        	$('.bind_level_3').find('input[id^=checkbox_level_]').each(function(){
            	$(this).prop('checked',false);
            	third_category_array = third_category_array.replace("," + $(this).val()+",",",");
            	//console.log('level_3_value:' + $(this).val()+",");
            });
        	var cancle_all_status = true;
        	$('.bind_level_2').find('input[id^=checkbox_level_]').each(function(){
            	if ($(this).is(':checked')){
            		cancle_all_status = false;
            		return false;
            	}            	
            });
            if (cancle_all_status){
            	$('#checkbox_' + first_category).prop('checked',false);
            }
        }
        if (second_category_not_check_nums == 0) second_category_all_status = true;
        console.log('first_category:' + first_category);       
        console.log('second_category_all_status:' + second_category_all_status);       
        console.log('first_category_array:' + first_category_array);       
        console.log('second_category_array:' + second_category_array);       
        console.log('third_category_array:'  + third_category_array);              
    }    
</script>       
<div layoutH="10" style="float:left; display:block; overflow:auto; width:440px; border:solid 1px #CCC; line-height:21px; background:#fff">
	<div class="panelBar">
		<ul class="toolBar">
			<li style="width:100%;">
				<div class="updateCate" onclick="" class="btn">二级分类</div>
				<?php
					if($categoryId == 36){
					    echo '<div class="setRate" onclick="setNextCommissionRate()">设置佣金比例</div>';
					}
				?>
			</li>          
		</ul>
	</div>
	<ul class="tree treeFolder ulselect bind_level_<?php echo $level; ?>" id="tree-menu" >
		<?php
			$str = '';
			foreach ($list as $key => $val) {
				$commission = '';
				if($val['category_id'] != 36){
					$commission = AliexpressCategoryCommissionRate::getCommissionRateText($val['category_id']);
				}
				$str .= "<li style='position:relative;'>";
				$str .= "<span>";
				$str .= "<input type='checkbox' id='checkbox_level_{$val['category_id']}' value='{$val['category_id']}' onclick='update_second_category($(this))' style='position:absolute; right:6px; ' />";				
				$str .= '<a  level="2" href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levelCategoryId_' . $val['category_id'].' rel="'.$val['category_id'].'" >'.$val['en_name'].'('.$val['cn_name'].')';
				//$str .= $commission.'</a>';
				$str .= '</a>';
				$str .= "<span>";
				$str .= '</li>';
			}
			echo $str;
		?>
	</ul>
</div>
<div id="aliexpressCategoryAccessPanel2" class="unitBox"></div>
<input type="hidden" name="category_ID" id="category_ID" value="" />
<?php } ?>
<script type="text/javascript">
/**
 * 设置栏目的佣金比例
 */
function setNextCommissionRate(){
    var categoryId = $('#category_ID').val();
    if(isNaN(categoryId) == true){
        alertMsg.error('请选择类目');
        return false;
    }

    var url ='/aliexpress/aliexpress_account_bind_category/setcommissionrate/categoryId/' + categoryId;
    $.pdialog.open(url, 'setCommissionRate', '设置栏目的佣金比例', {width:360, height:150});
}
</script>