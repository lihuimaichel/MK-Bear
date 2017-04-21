<?php
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/task_management.css', 'screen');
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
?>
<script language="javascript">
    $(function () {
        $("#saveData").click(function () {
            var appeal_type = $("#appeal_type").val();
            var appeal_description = $("#appeal_description").val();
            var id = $("#wid").val();
            if ('' == appeal_description) {
                alert('请输入申诉描述');
                return false;
            }
            $.ajax({
                type: 'post',
                url: 'task/task/process',
                data: {
                    id: id,
                    appeal_type: appeal_type,
                    appeal_description: appeal_description,
                },
                success: function (result) {
                    if (200 == result.statusCode) {
                        $.pdialog.closeCurrent();
                        navTab.reload(result.forward, {navTabId: result.navTabId});
                    }
                },
                dataType: 'json'
            });
        })
    })
</script>
<div class="appeals">
	<div class="appeals_main">
		<span>账号：<?php echo $data['account_short_name']; ?>     站点：<?php echo $data['site_name']; ?>   sku:<?php echo $data['sku']; ?></span><br/>
		<span>标题：<?php echo $data['sku_title'];?></span>
			申诉类型
            <select name="appeal_type" id="appeal_type">
                <option value="1">已解绑SKU</option>
                <option value="2">侵权违规SKU</option>
                <option value="3">无价格优势或者无市场</option>
                <option value="4">类目不符，类目被清退</option>
                <option value="5">不适合当前平台</option>
                <option value="6">其他</option>
            </select><br/>
			申诉描述<br/>
			<textarea id="appeal_description" rows="10" cols="50"></textarea><br/>
            <input type="hidden" id="wid" value="<?php echo $data['id'];?>">
            <input type="button" id="saveData" value="提交"/>
            <button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button>
	</div>
</div>