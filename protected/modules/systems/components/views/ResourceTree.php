<?php

/**
 * resources setting tree component
 * 
 * @author Bob <zunfengke@gmail.com>
 */
echo '<ul> ';
foreach ($assignResources as $key => $val) { 
    echo "<li>";
    $htmlOptions = array();
    echo CHtml::link($key.'Module', 'javascript:void(0);');
    echo "<ul>";
    foreach ($val as $key2 => $val2) {
        echo "<li>";
        echo CHtml::link($key2. 'Controller', 'javascript:void(0);');
        echo "<ul>";
        foreach ($val2 as $key3 => $val3) {
            echo "<li>";
            $htmlOptions['id'] = 'assign_resource_'. $val3;
            $htmlOptions['tvalue'] = $key3;
            $htmlOptions['checked'] = false;
            if ( in_array($val3, $assignedResources) ) {
                $htmlOptions['checked'] = true;
            } 
            echo CHtml::link('action '.ucfirst($key3), 'javascript:void(0);', $htmlOptions);
            echo '</li>';
        }
        echo "</ul>";
        echo '</li>';
    }
    echo "</ul>";
    echo '</li>';
}
echo '</ul>';
?>

