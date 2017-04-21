<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false;?>
<div class="pageContent">
    <?php 
        $tab2HtmlOptions = array( 'class' => 'j-ajax');
        if ( empty($model->id) ) {
            $tab2HtmlOptions['style'] = "display:none";
        }
        $data = array(
            'currentIndex'  => '0',
            'eventType'     => 'click',
            'tabsHeader'    => array(
                array( 
                    'text' => '基本资料',
                    'url'  => "/products/product/basicinformation/id/".$model->id,
                    'htmlOptions' => array( 'class' => 'j-ajax')
                ),
                array(
                    'text' => '产品属性', 
                    'url'  => "/products/product/productattributes/id/".$model->id,
                    'htmlOptions' => $tab2HtmlOptions
                ),
                array(
                    'text' => '中文描述',
                    'url'  => "products/product/langdescription/id/".$model->id,
                    'htmlOptions' => $tab2HtmlOptions
                ),
                array(
                    'text' => '英文描述',
                    'url'  => "products/product/langdescription/id/".$model->id."/langCode/en",
                    'htmlOptions' => $tab2HtmlOptions
                ), 
                array(
                    'text' => '物流信息',
                    'url'  => "products/product/logisticsinformation/id/".$model->id,
                    'htmlOptions' => $tab2HtmlOptions
                ),               
            ),
            'tabsContent' => array(),
        );  

        $count=count($data['tabsHeader']);

        for ($i=0;$i<$count;$i++){
            $data['tabsContent'][]=array( 'content' => '');
        }
        
    ?>
    <?php $this->renderPartial('_tabs', $data); ?>  
</div>
<script>
    $(function(){      
        setTimeout(function(){
            $('.tabsHeaderContent').find('ul li:first').find('a').trigger('click');
        }, 10);      
    });
</script>