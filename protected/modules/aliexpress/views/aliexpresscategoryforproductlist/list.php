<?php
if($list){
	Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpresscategoryforproductlist.js', CClientScript::POS_HEAD);
?>
<script type="text/javascript">
    $("a[id^=levelCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
</script>       
<div layoutH="10" style="float:left; display:block; overflow:auto; width:368px; border:solid 1px #CCC; line-height:21px; background:#fff">
	<div class="panelBar">
		<ul class="toolBar">
			<li style="width:100%;">
				
			</li>          
		</ul>
	</div>
	<ul class="tree treeFolder ulselect" id="tree-menu" >
		<?php
			$str = '';
			foreach ($list as $key => $val) {
				$str .= "<li style='position:relative;'>";
				$str .= "<span>";
				$str .= '<a href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levelCategoryId_' . $val['category_id'].' rel="'.$val['category_id'].'" style=" position:absolute; padding-left:44px; width:88%;">';
				$str .= $val['en_name'].'('.$val['cn_name'].')';
				$str .= '</a>';
				$subInfo = AliexpressCategory::model()->getSubCategory($val['category_id']);
				if (empty($subInfo)){
				    $str .= "<input type=\"button\" value=' 确定 ' onclick=\"addCategoryID({$val['category_id']})\" style=\"position:absolute; right:6px; height:21px; \" />";
				}
				$str .= '</span>';
				$str .= '</li>';
			}
			echo $str;
		?>
	</ul>
</div>
<div id="aliexpressCategoryAccessPanel2" class="unitBox"></div>
<input type="hidden" name="category_ID" id="category_ID" value="" />
<?php } ?>
