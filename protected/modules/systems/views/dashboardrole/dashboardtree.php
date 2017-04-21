<?php

/**
 * menu setting tree component
 * 
 * @author shenll
 */
define('TYPE_RESOURCE', 'resource');
echo '<ul> ';
if ($type == TYPE_RESOURCE) {
    echo subMenu($dashboardList, $type, $resources,$menuId);
} else {
    echo subMenu($dashboardList, $type,'','',$menuId);
}
echo '</ul>';
function subMenu($data, $type, $resources = array(),$menuId='0') {
    $str = '';
    foreach ($data as $key => $val) {
        $str .= "<li>";
        $htmlOptions = array('id' => 'treeDashboard_' . $val['id']);
        if (!empty($resources) && in_array('dashboard_' . $val['id'], $resources) && empty($val['submenu'])) {
            $htmlOptions['checked'] = true;
        }
        if ($type == TYPE_RESOURCE) {
            $htmlOptions['id'] = 'dashboard_' . $val['id'];
            $htmlOptions['tvalue'] = $val['id'];
        }
        $str .= CHtml::link($val['name'], 'javascript:void(0);', $htmlOptions);
        if (!empty($val['submenu']) && $menuId != $val['id']) {
            $str .= "<ul>";
            $str .= subMenu($val['submenu'], $type, $resources,$menuId);
            $str .= "</ul>";
        }
        $str .= '</li>';
    }
    return $str;
}
?>

