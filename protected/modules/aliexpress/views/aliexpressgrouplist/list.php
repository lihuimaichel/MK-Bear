<script type="text/javascript">
$("a[id^=groupId_]").ProductGroup();
	
function divSubmitRefresh(form) {
	var $form = $(form);
	$.ajax({
		type: form.method || 'POST',
		url: $form.attr("action"),
		data: $form.serializeArray(),
		dataType: "json",
		cache: false,
		success: function(json) {
            	DWZ.ajaxDone(json);
//             	self.location.reload();
        },
		error: DWZ.ajaxError
	});
	return false;
}
</script>
<form method="post" action="/aliexpress/aliexpressgrouplist/Getgrouplist/account_id/<?php echo $accountId;?>" class="pageForm" onsubmit="return divSubmitRefresh(this)">
    <div class="panelBar">  
        <?php echo CHtml::submitButton(Yii::t('aliexpress', 'Synchronization')) ?>
    </div>
    <div layoutH="50" style="float:left; display:block; overflow:auto; width:600px; border:solid 1px #CCC; line-height:21px; background:#fff">   
	    <ul class="tree treeFolder" rel="treeGroup" id="treeGroup">
	        <li>
	            <a id="groupId_0"  name="groupName_<?php echo $accountId;?>"><?php echo Yii::t('aliexpress', 'Product Group List')?></a>
	            <?php echo $this->renderPartial('aliexpress.components.views.GroupTree', array( 'class' => 'tree treeFolder', 'id' => 'treeGroup', 'accountId' => $accountId,'menuId' => ''));?>
	        </li>
	    </ul>
	</div>
</form>