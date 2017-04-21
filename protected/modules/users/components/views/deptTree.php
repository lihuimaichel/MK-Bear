<?php
/**
 * department tree component
 * 
 * @author ethanhu
 */
$list = Dep::getTreeList();
echo '<ul class="'. $class .'" id="'. $id .'"><li>';
echo CHtml::link(Yii::t('users', 'Organization'), '/users/users/list/target/jbsxBox', array('id'=>'dep_0','target' => 'ajax', 'rel' => 'jbsxBox'));
echo '<ul>';
echo subMenu($list,$menuId);
echo '</ul></li></ul>';
function subMenu($data,$menuId=0) {
	$str = '';      
    foreach ($data as $key => $val) {
        $str .= "<li>";      
        $str .= CHtml::link($val['name'],'/users/users/list/target/jbsxBox/department_id/'.$val['id'], array('id'=>'dep_'.$val['id'],'target' => 'ajax', 'rel' => 'jbsxBox'));       
        if (! empty($val['subdept']) && $menuId!=$val['id']) {
            $str .= "<ul>";
            $str .= subMenu($val['subdept'],$menuId);
            $str .= "</ul>";
        }       
        $str .= '</li>';
    }
    return $str;
}
?>
