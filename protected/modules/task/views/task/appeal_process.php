<?php
$baseUrl = Yii::app()->request->baseUrl;
Yii::app()->clientScript->registerCssFile($baseUrl . '/css/dashboard/task_management.css', 'screen');
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
?>
<script language="javascript">
    $(function () {
        $("#saveData").click(function () {
            var appeal_status = $("input[name='appeal_status']:checked").val();
            var appeal_reject = $("#appeal_reject").val();
            var id = $("#wid").val();
            if (3 == appeal_status) {
                if ('' == appeal_reject) {
                    alert('请输入驳回原因');
                    return false;
                }
            }

            $.ajax({
                type: 'post',
                url: 'task/task/appealprocess',
                data: {
                    id: id,
                    appeal_status: appeal_status,
                    appeal_reject: appeal_reject,
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
        <span>销售人员：<?php echo $data['seller_user_name']; ?><input type="hidden" id="wid" value="<?php echo $data['id']; ?>"></span><br/>
		<span>账号：<?php echo $data['account_short_name'];?> &nbsp; &nbsp; 站点：<?php echo $data['site_name']; ?>&nbsp; &nbsp;sku：<?php echo $data['sku']; ?></span><br/>
		<span>标题：<?php echo $data['sku_title'];?></span><br />
        <span>申诉类型：<?php echo $data['appeal_type']; ?></span><br />
        <span>操作类型： <input type="radio" name="appeal_status" value="2" checked>同意
                        <input type="radio" name="appeal_status" value="3">驳回</span><br/>
        <span>
            驳回原因：<br />
            <textarea rows="10" cols="60" id="appeal_reject"></textarea>
        </span>
        <p>注意：同意后，会自动重新申请一条新任务来补充量！！！</p>
        <input type="button" id="saveData" value="提交" />
        <button type="button" class="close"><?php echo Yii::t('system', 'Cancel') ?></button>
	</div>
</div>