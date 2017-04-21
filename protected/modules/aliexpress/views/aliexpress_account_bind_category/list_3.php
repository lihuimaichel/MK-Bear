<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpressaccountbindcategorylist.js', CClientScript::POS_HEAD);
if($list){
?>
<script type="text/javascript">
    $("a[id^=levemCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
    function update_third_category(obj){       
    	var category_id = obj.val();
    	third_category_array = third_category_array.replace("," + category_id+",",",");
        if (obj.is(':checked')){       	
        	third_category_array += category_id + ',';
        	$('#checkbox_level_' + second_category).prop('checked',true);
        } else {
            var cancle_all_status = true;
        	$('.bind_level_3').find('input[id^=checkbox_level_]').each(function(){
            	if ($(this).is(':checked')){
            		cancle_all_status = false;
            		return false;
            	}            	
            });
            if (cancle_all_status){
            	$('#checkbox_level_' + second_category).prop('checked',false);
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
        }
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
				<div class="updateCate" onclick="" class="btn">三级分类</div>
				<?php
					if($categoryId == 36){
					    echo '<div class="setRate" onclick="setNextCommissionRate()">设置佣金比例</div>';
					}
				?>
			</li>          
		</ul>
	</div>
	<ul class="tree treeFolder bind_level_<?php echo $level; ?>" id="tree-menu" parentCategoryID="">
		<?php
			$str = '';
			foreach ($list as $key => $val) {
				$str .= "<li style='position:relative;'>";
				$str .= "<span>";
				$str .= "<input type='checkbox' id='checkbox_level_{$val['category_id']}' value='{$val['category_id']}' onclick='update_third_category($(this))' style='position:absolute; right:6px; ' />";
				$str .= '<a level="3" href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levemCategoryId_' . $val['category_id'].' >'.$val['en_name'].'('.$val['cn_name'].')</a>';
				$str .= "</span>";
				$str .= '</li>';
			}
			echo $str;
		?>
	</ul>
</div>
<div id="aliexpressCategoryAccessPanel3" class="unitBox"></div>
<?php } ?>