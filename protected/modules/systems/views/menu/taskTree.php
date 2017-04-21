<?php
/**
 * 权限任务列表
 * @author Gordon
 * @since 2014-06-05
 */
$messages = include_once (Yii::getPathOfAlias('webroot').'/protected/messages/zh_cn/resource.php');
?>
<form method="post" action="<?php echo Yii::app()->createUrl($this->route); ?>" class="pageForm" onsubmit="return divSubmitRefresh(this)">
	<div class="pageFormContent" layoutH="56" style="border:1px solid #B8D0D6;"> 
 		<table class="list" width="100%" border="0" cellspacing="1" cellpadding="3"> 
			<?php 
				foreach($menuList[$menuID]['submenu'] as $key=>$item):
					if( count($operationArr['menu_'.$key])<=0 ) continue;
			?>
			<tr align="center">
					<td width="15%" align="left"><input type="checkbox" class="allchoose" /><?php echo $item['name'];?></td>
         			<td>
         				<ul>
         					<?php 
         					foreach($operationArr['menu_'.$key] as $opration): 
         						$ex = explode('_',$opration);
         						$controller = $ex[2];
         						$action = $ex[3];
         						$check = '';
         						if( in_array($opration,$loginRoleResources) ){
         							$check = 'checked="checked"';
         						}
         					?>
         					<li style="width:250px;float:left;">
         						<input class="resource" style="float:left;" type="checkbox" value="<?php echo $opration;?>" <?php echo $check; ?> name="auth[<?php echo $key?>][]" id="<?php echo 'menu_'.$key.'_'.$opration;?>" />
         						<label style="text-align:left;" for="<?php echo 'menu_'.$key.'_'.$opration;?>"><?php echo $messages[$controller][$action] ? $messages[$controller][$action] : $action;?></label>
         					</li>
         					<?php endforeach; ?>
         				</ul>
         			</td>
    		</tr>							
			<?php endforeach;?>
		</table>
	</div>
	<div class="formBar">
	    <ul>
	    	<li>
	            <div class="buttonActive">
	                <div class="buttonContent">                        
	                    <button type="submit"><?php echo Yii::t('common', 'Save and Close')?></button>
	                    <input type="hidden" value="1" name="saveSubmit" />
	                    <input type="hidden" value="<?php echo $uid;?>" name="uid" />
	                    <input type="hidden" value="<?php echo $menuID;?>" name="menu_id" />
	                </div>
	            </div>
	        </li>
	    </ul>
	</div>
</form>
<script type="text/javascript">
<!--
$('.allchoose').click(function(){
	var flag = $(this).attr('checked');
	var swith = false;
	if(flag){
		swith = true;
	}
	$(this).parent().next('td').find('input[type="checkbox"]').attr('checked',swith);
});    
function divSubmitRefresh(form) {
    var $form = $(form);
    var resources = [];
    $form.find('.resource:checked').each(function() {
    	var resourcesId = $(this).val();
        resources.push(resourcesId);
    });
    var resourcesStr = '';
    if (resources.length > 0) {
        resourcesStr = resources.join();
    } else {
    	resourcesStr = '';
    }
    $.ajax({
        type: form.method || 'POST',
        url: $form.attr("action"),
        data: {
			uid:$('input[name="uid"]').val(),
			menu_id:$('input[name="menu_id"]').val(),
			resources:resourcesStr,
			saveSubmit:1
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
//-->
</script>