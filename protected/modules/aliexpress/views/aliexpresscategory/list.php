<?php
if($list){
	Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpresscategorylist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=levelCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
</script>       
<div layoutH="10" style="float:left; display:block; overflow:auto; width:440px; border:solid 1px #CCC; line-height:21px; background:#fff">
	<div class="panelBar">
		<ul class="toolBar">
			<li style="width:100%;">
				<div class="updateCate" onclick="updateCategory(<?php echo $categoryId; ?>)" class="btn">同步二级及下属栏目</div>
				<?php
					if($categoryId == 36){
					    echo '<div class="setRate" onclick="setNextCommissionRate()">设置佣金比例</div>';
					}
				?>
			</li>          
		</ul>
	</div>
	<ul class="tree treeFolder ulselect" id="tree-menu" >
		<?php
			$str = '';
			foreach ($list as $key => $val) {
				$commission = '';
				if($val['category_id'] != 36){
					$commission = AliexpressCategoryCommissionRate::getCommissionRateText($val['category_id']);
				}
				$str .= "<li id = >";
				$str .= '<a href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levelCategoryId_' . $val['category_id'].' rel="'.$val['category_id'].'" >'.$val['en_name'].'('.$val['cn_name'].')'.$commission.'</a>';
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

    var url ='/aliexpress/aliexpresscategory/setcommissionrate/categoryId/' + categoryId;
    $.pdialog.open(url, 'setCommissionRate', '设置栏目的佣金比例', {width:360, height:150});
}
</script>