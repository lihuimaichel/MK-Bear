<?php
/**
 * 产品分组账号列表
 */
$list = AliexpressGroupList::getAccountList();
echo '<ul class="tree treeFolder" id="tree-menu" > ';
echo subMenu($list);
echo '</ul>';
function subMenu($data) {
	$str = '';
	foreach ($data as $key => $val) {
		$str .= "<li id = >";
		$str .= '<a href="javascript::void(0);" id =accountId_' . $val['id'].' >'.$val['short_name'].'</a>';
		if ( !empty($val['submenu'])) {
			$str .= "<ul>";
			$str .= subMenu($val['submenu']);
			$str .= "</ul>";
		}
		$str .= '</li>';
	}
	return $str;
}
?>