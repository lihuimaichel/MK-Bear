<?php

/**
 * menu setting tree component
 * 
 * @author Bob <zunfengke@gmail.com>
 */

define('TYPE_RESOURCE', 'resource');
define('TYPE_TASK', 'menuTask');

$menuList = Menu::getTreeList();

$uid = isset($uid) ? $uid : '';

echo '<ul> ';
if ($type == TYPE_RESOURCE || $type == TYPE_TASK) {
    echo subMenu($menuList, $type, $resources, $loginRoleResources,$menuId, $uid);
} else {
    echo subMenu($menuList, $type,'','',$menuId, $uid);
}
echo '</ul>';
function subMenu($data, $type, $resources = array(), $loginRoleResources = array(),$menuId='0', $uid='') {
    $str = '';
    foreach ($data as $key => $val) {
        if (($type == TYPE_RESOURCE || $type == TYPE_TASK) && !User::isAdmin() &&
                !in_array('menu_' . $val['id'], $loginRoleResources)) {
            continue;
        }
        $str .= "<li>";
        if($type == 'menuTask'){
        	$htmlOptions = array('id' => 'menu_' . $val['id']);
        }else{
        	$htmlOptions = array('id' => 'treeItem_' . $val['id']);
        }
        
        if (!empty($resources) && in_array('menu_' . $val['id'], $resources) && empty($val['submenu'])) {
            $htmlOptions['checked'] = true;
        }
        if ($type == TYPE_RESOURCE) {
            $htmlOptions['id'] = 'menu_' . $val['id'];
            $htmlOptions['tvalue'] = $val['id'];
        }
        if($type == TYPE_TASK){
        	$htmlOptions['mask'] = '1';
        	$htmlOptions['width'] = '600';
        	$htmlOptions['height'] = '500';
        	$htmlOptions['lookupGroup'] = "org3";
        	$str .= CHtml::link($val['name'], '/systems/menu/operationList/target/dialog/task/menu_'.$val['id'].'/'.($uid ? 'uid/'.$uid : ''), $htmlOptions);
        }else{
        	$str .= CHtml::link($val['name'], 'javascript:void(0);', $htmlOptions);
        }
        if (!empty($val['submenu']) && $menuId != $val['id']) {
            $str .= "<ul>";
            $str .= subMenu($val['submenu'], $type, $resources, $loginRoleResources,$menuId,$uid);
            $str .= "</ul>";
        }
        $str .= '</li>';
    }
    return $str;
}
?>

