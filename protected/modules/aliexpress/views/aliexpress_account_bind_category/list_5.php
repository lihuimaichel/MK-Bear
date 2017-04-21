<?php
Yii::app()->clientScript->registerScriptFile('/js/custom/aliexpressaccountbindcategorylist.js', CClientScript::POS_HEAD);
if($list){
?>
<script type="text/javascript">
    $("a[id^=levefCategoryId_]").aliexpressCategoryListLevelAjax(<?php echo $level; ?>);
</script>       
<div layoutH="10" style="float:left; display:block; overflow:auto; width:380px; border:solid 1px #CCC; line-height:21px; background:#fff">
	<div class="panelBar">
		<ul class="toolBar">
			<li></li>          
		</ul>
	</div>
	<ul class="tree treeFolder" id="tree-menu" >
		<?php
			$str = '';
			foreach ($list as $key => $val) {
				$str .= "<li id = >";
				$str .= '<a href="javascript::void(0);" title="'.$val['en_name'].'('.$val['cn_name'].')" id=levefCategoryId_' . $val['category_id'].' >'.$val['en_name'].'('.$val['cn_name'].')</a>';
				$str .= '</li>';
			}
			echo $str;
		?>
	</ul>
</div>
<div id="aliexpressCategoryAccessPanel5" class="unitBox"></div>
<?php } ?>