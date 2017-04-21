<script type="text/javascript">
$("a[id^=page_]").addtoMianCmDialog(); 
</script>
<div class="accordionHeader">
    <h2><span>Folder</span><?php echo Yii::t('system', 'History menu');?>
    <?php echo CHtml::link(Yii::t('system', 'Refresh'), 'javascript: void(0);', array(
    		'class' => 'btnRefresh',
    		'style' => 'display:block;margin-left:100px;margin-top:-23px;',
    		'title' => Yii::t('system', 'Refresh'),
    		'onclick' => 'refreshHistoryUrl();',
    ))?>
    </h2>
</div>
<div class="accordionContent">
    <?php $menuList = UebModel::model('menu')->getHistoryMenuList(); ?>     
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

<script>         
// var refreshHistoryUrl = function(){
// 	$.ajax({
// 			type: 'post',
// 			url: '/systems/menu/refreshHistoryUrl',
// 			datatype: 'html',
// 			async: false,
// 			success: function(data){
// 				if(data){
// 					$('#historySpan').html(data);
// 				}
// 			}
// 	});	
// }

</script>

