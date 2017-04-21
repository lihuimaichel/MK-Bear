<?php
/**
 * 操作权限列表
 * @author Gordon
 * @since 2014-06-05
 */
?>












<form method="post" action="<?php echo Yii::app()->createUrl($this->route); ?>" class="pageForm" onsubmit="return divSubmitRefresh(this)" >
	<div layoutH="35" style="float:left; display:block; overflow:auto; width:98%; border:solid 1px #CCC; line-height:21px; background:#fff">
		<ul class="tree treeFolder treeCheck expand" rel = "resourceTreePanel">
			<li>
                <a><?php echo Yii::t('users', 'All Resources') ?></a>
                <?php 
               		$messages = include(Yii::getPathOfAlias('webroot').'/protected/messages/zh_cn/resource.php');
               		$common = $messages['common'];
                	echo '<ul> ';
                	foreach ($operationList as $key => $val) {
						echo "<li>";
						$htmlOptions = array();
                		echo CHtml::link($val['name'], 'javascript:void(0);');	
                		echo "<ul>";
                		foreach($val['operation'] as $module=>$item){
                			echo "<li>";
                			echo CHtml::link($module.' Module', 'javascript:void(0);');
                			echo "<ul>";
                			foreach($item as $controller=>$itm){
                				echo "<li>";
								echo CHtml::link($controller.' Controller', 'javascript:void(0);');
								echo "<ul>";
								foreach($itm as $action=>$v){
									$htmlOptions['id'] = 'resource_'.$module.'_'.$controller.'_'.$v;
									$htmlOptions['tvalue'] = $v;
									$htmlOptions['checked'] = false;
									if ( in_array($htmlOptions['id'], $assignedResources) ) {
										$htmlOptions['checked'] = true;
									}
									echo "<li>";
									//add by ethan 2014.09.02,公用翻译
									if(array_key_exists($v,$messages[$controller])){
										echo CHtml::link($messages[$controller][$v], 'javascript:void(0);',$htmlOptions);
									}
									elseif(array_key_exists($v,$common)){
										echo CHtml::link($common[$v], 'javascript:void(0);',$htmlOptions);
									}
									else{
										echo CHtml::link(Yii::t('resource',$controller.'[arr#%%#arr]'.$v), 'javascript:void(0);',$htmlOptions);	//t()第二个参数改为可能'[arr#%%#arr]'连接格式，相应方法改变
									}
// 									if(array_key_exists($v,$common)){
// 										echo CHtml::link($common[$v], 'javascript:void(0);',$htmlOptions);	
// 									}else{
// 										echo CHtml::link(Yii::t('resource',$controller.'[arr#%%#arr]'.$v), 'javascript:void(0);',$htmlOptions);	//t()第二个参数改为可能'[arr#%%#arr]'连接格式，相应方法改变
// 									}
									//add end
									echo "</li>";
								}
								echo "</ul>";
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
            </li>
        </ul>  
    </div>	
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">   
                    	<?php echo CHtml::hiddenField('uid', $uid); ?>  
                    	<?php echo CHtml::hiddenField('task', $task); ?>                       
                        <?php echo CHtml::hiddenField('resources', @$_REQUEST['resources'], array('id' => 'resources')); ?>                      
                        <button type="submit"><?php echo Yii::t('system', 'Save') ?></button>                     
                    </div>
                </div>
            </li>          
        </ul>
    </div>
</form>
<script type="text/javascript">
    function divSubmitRefresh(form) {
        var $form = $(form);
        var resources = [];
        $form.find('.checked').each(function() {
            if ($(this).parent('div').find('a').attr('id') != null) {
                var resourcesId = $(this).parent('div').find('a').attr('id');
                resources.push(resourcesId);
            }
        });
        if (resources.length > 0) {
            resources.join()
            $('#resources').val(resources);
        } else {
            $('#resources').val('');
        }
        $.ajax({
            type: form.method || 'POST',
            url: $form.attr("action"),
            data: {
				uid:$('#uid').val(),
				task:$('#task').val(),
				resources:$('#resources').val()
            },
            dataType: "json",
            cache: false,
            success: function(json) {
                dialogAjaxDone(json);
            },
            error: DWZ.ajaxError
        });
        return false;
    }
</script>