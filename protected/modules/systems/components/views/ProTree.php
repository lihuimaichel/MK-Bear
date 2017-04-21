<?php
/**
 * menu sider tree component
 * 
 * @author Bob <zunfengke@gmail.com>
 */
$list = Region::get_province();
//echo '<ul class="tree expand" id="depTreePanel"><li>';
echo '<ul class="'. $class .'" id="'. $id .'"><li>';
echo CHtml::link(Yii::t('users', 'Province List'), array('id'=>'pro_0','target' => 'ajax', 'rel' => 'cityBox'));
echo '<ul>';
echo subMenu($list);
echo '</ul></li></ul>';
function subMenu($data) {
	$str = '';      
    foreach ($data as $key => $val) {
        $str .= "<li>";      
        $str .= CHtml::link($val['region_name'],'/systems/region/city/pro_id/'.$val['id'], array('onclick'=>'showarea("pro");','id'=>'pro_'.$val['id'],'target' => 'ajax', 'rel' => 'cityBox'));       
        if (! empty($val['subdept'])) {
            $str .= "<ul>";
            $str .= subMenu($val['subdept']);
            $str .= "</ul>";
        }       
        $str .= '</li>';
    }
    return $str;
}
?>
<script type="text/javascript">
    
</script>
