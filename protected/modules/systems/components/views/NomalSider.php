<script type="text/javascript">
$("a[id^=page_]").addtoMianCmDialog(); 
</script>
<div class="accordionHeader">
    <h2><span>Folder</span><?php echo Yii::t('system', 'Commonly Used Menu');?></h2>
</div>
<div class="accordionContent">
    <?php $menuList = UebModel::model('menu')->getHighestFrequencyMenuList();?>       
    <ul class="tree treeFolder">
        <?php foreach ($menuList as $key => $val): ?>
            <li>               
                <?php
                echo CHtml::link($val['menu_display_name'], Yii::app()->baseUrl . $val['menu_url'], array(
                    'target' => "navTab",
                    'rel' => "page" . $val['id'],
					'id'  => "page_". $val['id'],
                ));
                ?>
            </li>	
<?php endforeach; ?>
    </ul>        
</div>
