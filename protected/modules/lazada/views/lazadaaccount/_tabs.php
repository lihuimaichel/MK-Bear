<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
    <?php 
        $tab2HtmlOptions = array( 'class' => 'j-ajax');
        if ( empty($model->order_id) ) {
            $tab2HtmlOptions['style'] = "display:none";
        }
        $data = array(
            'currentIndex'  => '0',
            'eventType'     => 'click',
            'tabsHeader'    => array(
                array( 
                    'text' => Yii::t('system', 'Basic Information'),
                    'url'  => "lazada/lazadaaccount/basic/id/".$model->id.(isset($do) ? "/do/".$do : ''),
                    'htmlOptions' => array( 'class' => 'j-ajax')
                ),
            	
                array(
                    'text' => Yii::t('order', 'Product Infomation'),
                    'url'  => "lazada/lazadaaccount/view/id/".$model->id.(isset($do) ? "/do/".$do : ''),
                    'htmlOptions' => array( 'class' => 'j-ajax')
                ),
            ),
            'tabsContent' => array(
            	array( 'content' => ''),
            	array( 'content' => ''),
            ),
        );     
    ?>
    <?php $this->renderPartial('application.components.views._tabs', $data); ?>  
</div>
<script>
    $(function(){      
        setTimeout(function(){
            $('.tabsHeaderContent').find('ul li:first').find('a').trigger('click');
        }, 10);      
    });
</script>
