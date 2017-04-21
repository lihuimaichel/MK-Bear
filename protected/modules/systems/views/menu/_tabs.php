<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
    <?php 
        $data = array(
            'currentIndex'  => '0',
            'eventType'     => 'click',
            'tabsHeader'    => array(),
            'tabsContent' => array(),
        );   
        foreach($menuList as $key=>$list){
        	 $data['tabsHeader'][] = array( 
                    'text' => $list['name'],
                    'url'  => '/systems/menu/taskTree/menu/'.$key.'/uid/'.$uid,
                    'htmlOptions' => array( 'class' => 'j-ajax')
             );
        	 $data['tabsContent'][] = array( 'content' => '');
        }  
    ?>
    <?php $this->renderPartial('application.components.views._tabs', $data); ?>  
</div>
<script>	
$(function(){      
    setTimeout(function(){
        $('.tabsHeaderContent').find('ul li:first').find('a').trigger('click');
    }, 10);      
});
</script>.
