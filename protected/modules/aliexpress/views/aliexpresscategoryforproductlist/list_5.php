<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpresscategoryforproductlist.js', CClientScript::POS_HEAD);
if($list){
?>
<script type="text/javascript">
    $("a[id^=levefCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
</script>       
<div layoutH="10" style="float:left; display:block; overflow:auto; width:338px; border:solid 1px #CCC; line-height:21px; background:#fff">
	<div class="panelBar">
		<ul class="toolBar">
			<li></li>          
		</ul>
	</div>
	<ul class="tree treeFolder" id="tree-menu" >
		<?php
			$str = '';
			foreach ($list as $key => $val) {				
				$str .= "<li style='position:relative;'>";
				$str .= "<span>";
				$str .= '<a href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levefCategoryId_' . $val['category_id'].' style=" position:absolute; padding-left:44px; width:88%; " >';
				$str .= $val['en_name'].'('.$val['cn_name'].')';
				$str .= '</a>';
				$subInfo = AliexpressCategory::model()->getSubCategory($val['category_id']);
				if (empty($subInfo)){
				    $str .= "<input type=\"button\" value=' 确定 ' onclick=\"addCategoryID({$val['category_id']})\" style=\"position:absolute; right:6px; height:22px; \" />";
				}
				$str .= '</span>';
				$str .= '</li>';
			}
			echo $str;
		?>
	</ul>
</div>
<div id="aliexpressCategoryAccessPanel5" class="unitBox"></div>
<?php } ?>