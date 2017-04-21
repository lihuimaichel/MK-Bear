<form method="post" action="systems/dashboardrole/list" class="pageForm" onsubmit="return divSubmitRefresh(this)">
    <div class="panelBar">  
        <?php echo CHtml::submitButton(Yii::t('system', 'Save')) ?>
        <?php echo CHtml::hiddenField('roleId', @$_REQUEST['roleId']); ?>
        <?php echo CHtml::hiddenField('userId', @$_REQUEST['userId']); ?>
        <?php echo CHtml::hiddenField('resources', @$_REQUEST['resources'], array('id' => 'resources')); ?>
    </div>
    <div layoutH="38" style="float:left; display:block; overflow:auto; width:99%; border:solid 1px #CCC; line-height:21px; background:#fff">   
        <ul class="tree treeFolder treeCheck expand">
            <li>
                <a id="treeDashboard_0" ><?php echo Yii::t('system', 'All Dashboard') ?></a>
                <?php echo $this->renderPartial('dashboardtree', array(
                    'resources' => $resources,
                    'type' => 'resource',
                    'dashboardList' => $dashboardList,
                	'menuId' => '')); 
                ?>
            </li>
        </ul>  
    </div>	
</form>
<script type="text/javascript">
    function divSubmitRefresh(form) {
        var $form = $(form);
        var resources = [];
        $form.find('.checked').each(function() {
            resources.push($(this).parent('div').find('a').attr('id'));
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
                DWZ.ajaxDone(json);
            },
            error: DWZ.ajaxError
        });
        return false;
    }
</script>





