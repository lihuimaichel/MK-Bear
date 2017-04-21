<script type="text/javascript">
    $("a[id^=assign_resource_]").resourceCmDialog(); 
</script>
<form method="post" action="<?php echo Yii::app()->createUrl($this->route); ?>" class="pageForm" onsubmit="return divSubmitRefresh(this)" >   
    <div layoutH="35" style="float:left; display:block; overflow:auto; width:98%; border:solid 1px #CCC; line-height:21px; background:#fff">   
        <ul class="tree treeFolder treeCheck expand" rel = "resourceTreePanel">
            <li>
                <a><?php echo Yii::t('users', 'All Resources') ?></a>
                <?php echo $this->renderPartial('systems.components.views.ResourceTree', array('assignResources' => $assignResources, 'assignedResources' => $assignedResources)); ?>
            </li>
        </ul>  
    </div>	
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                         
                        <?php echo CHtml::hiddenField('id', @$_REQUEST['id']); ?>                        
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
                var resourcesId = $(this).parent('div').find('a').attr('id').substr(16);
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
            data: $form.serializeArray(),
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





