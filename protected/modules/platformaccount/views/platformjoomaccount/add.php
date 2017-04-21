<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'menuForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl("/platformaccount/platformjoomaccount/add"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:240px;">
            <input id="PlatformJoomAccount_ID" value="" type="hidden" class="textInput">
            <div class="row">
                <?php echo $form->labelEx($model, 'account'); ?>
                <?php echo $form->textField($model, 'account',array('size'=>48)); ?>
                <?php echo $form->error($model, 'account'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'account_name'); ?>
	        	<?php echo $form->textField($model, 'account_name',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'account_name'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'department_id'); ?>
                <?php echo $form->dropDownList($model, 'department_id', $departmentList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'department_id'); ?>
            </div>
			<div class="row">
	            <?php echo $form->labelEx($model, 'client_id'); ?>
                <?php echo $form->textField($model, 'client_id',array('size'=>48)); ?>
                <?php echo $form->error($model, 'client_id'); ?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'client_secret'); ?>
                <?php echo $form->textField($model, 'client_secret',array('size'=>48)); ?>
                <?php echo $form->error($model, 'client_secret'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'redirect_uri'); ?>
                <?php echo $form->textField($model, 'redirect_uri',array('size'=>48)); ?>
                <?php echo $form->error($model, 'redirect_uri'); ?>
            </div>
            <div class="row" style="overflow:hidden;padding-left:220px;">
                <div class="button"><div class="buttonContent"><button type="button" onclick="addSave()">保存并跳转授权</button></div></div>
            </div>
            <div class="row" id="rowID" style="display:none;">
                <label for="PlatformJoomAccount_code">code</label>
                <input size="48" id="PlatformJoomAccount_code" type="text" class="textInput">
                <span class="errorMessage"><a href="javascript:void(0)" onclick="getToken()">点击获取token</a></span>
            </div>
        </div>               
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close">关闭</button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
    function addSave(){
        var account = $("#PlatformJoomAccount_account").val();
        if(account == ''){
            alertMsg.error('账号名称不能为空');
            return false;
        }

        var accountName = $("#PlatformJoomAccount_account_name").val();
        if(accountName == ''){
            alertMsg.error('账号简称不能为空');
            return false;
        }

        var departmentID = $("#PlatformJoomAccount_department_id").val();
        if(departmentID == ''){
            alertMsg.error('所属部门必须选择');
            return false;
        }

        var clientId = $("#PlatformJoomAccount_client_id").val();
        if(clientId == ''){
            alertMsg.error('Client Id不能为空');
            return false;
        }

        var clientSecret = $("#PlatformJoomAccount_client_secret").val();
        if(clientSecret == ''){
            alertMsg.error('Client Secret不能为空');
            return false;
        }

        var redirectUri = $("#PlatformJoomAccount_redirect_uri").val();
        if(redirectUri == ''){
            alertMsg.error('Redirect Uri不能为空');
            return false;
        }

        var postData = {'account':account, 'account_name':accountName, 'department_id':departmentID, 'client_id':clientId, 'client_secret':clientSecret, 'redirect_uri':redirectUri};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformjoomaccount/addsave')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                window.open(data.url);
                $("#rowID").css({'display':'block'});
                $("#PlatformJoomAccount_ID").val(data.aid);
                // alertMsg.correct(data.message);             
            } else {
                alertMsg.error(data.message);
            }
        }, 'json');
    }


    function getToken(){
        var code = $("#PlatformJoomAccount_code").val();
        if(code == ''){
            alertMsg.error('code不能为空');
            return false;
        }

        var id = $("#PlatformJoomAccount_ID").val();
        if(id == ''){
            alertMsg.error('账号ID不能为空');
            return false;
        }

        var postData = {'code':code, 'id':id};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformjoomaccount/tokensave')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                alertMsg.correct(data.message);             
            } else {
                alertMsg.error(data.message);
            }
        }, 'json');
    }
</script>