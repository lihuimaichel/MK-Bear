<?php
/**
 * 产品分组账号列表
 */

$accountId	= isset($accountId) ? $accountId : '';
$menuId		= isset($menuId) ?  $menuId : '';
$list		= AliexpressGroupList::getTreeList($accountId);
echo '<ul> ';
echo subMenu($list, $menuId, $accountId);
echo '</ul>';
function subMenu($data, $menuId='0', $accountId) {
    $str = '';
    foreach ($data as $key => $val) {
        $str .= "<li>";
        
        $htmlOptions = array('id' => 'groupId_' . $val['group_id'], 'name' => 'groupName_' . $val['account_id']);
        $str .= CHtml::link($val['group_name'], 'javascript:void(0);', $htmlOptions);
        if (!empty($val['childGroup']) && $menuId != $val['group_id']) {
            $str .= "<ul>";
            $str .= subMenu($val['childGroup'], $val['group_id'], $val['account_id']);
            $str .= "</ul>";
        }
        $str .= '</li>';
    }
    return $str;
}
?>