<?php
/**
 * menu sider tree component
 * 
 * @author Bob <zunfengke@gmail.com>
 */
$menuList = Menu::getTreeList(1, 1);

echo '<ul class="tree treeFolder" id="tree-menu" > ';
echo subMenu($menuList[$parentId]['submenu']);
echo '</ul>';
function subMenu($data) {
    $str = '';      
    foreach ($data as $key => $val) {
        if (! Menu::checkAccess('menu_'.$val['id']) ) {
            continue;
        }
        if (! MenuPrivilege::model()->checkMenuAccess($val['id']) ) {
        	continue;
        }
        $str .= "<li>";
        if (! empty($val['menu_url']) ) {
            $str .=  '<a href="'.Yii::app()->baseUrl. $val['menu_url'].'" target="navTab" id="page_'.$val['id'].'" rel="page'.$val['id'].'">'.$val['name'].'</a>';
        } else {
            $str .=  '<a href="javascript::void(0);" >'.$val['name'].'</a>';
        }
        
        if (! empty($val['submenu'])) {
            $str .= "<ul>";
            $str .= subMenu($val['submenu']);
            $str .= "</ul>";
        }       
        $str .= '</li>';
    }
    return $str;
}
?>