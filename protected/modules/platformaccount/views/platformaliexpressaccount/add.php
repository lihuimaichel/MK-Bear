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
        'action' => Yii::app()->createUrl("/platformaccount/platformaliexpressaccount/add"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:200px;">
            <input id="PlatformAliexpressAccount_ID" value="" type="hidden" class="textInput">
            <div class="row">
                <?php echo $form->labelEx($model, 'email'); ?>
                <?php echo $form->textField($model, 'email',array('size'=>48,'maxlength'=>128,'empty'=>"不能为空")); ?>
                <?php echo $form->error($model, 'email'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'short_name'); ?>
	        	<?php echo $form->textField($model, 'short_name',array('size'=>48,'maxlength'=>128,'empty'=>"不能为空")); ?>
	           	<?php echo $form->error($model, 'short_name'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'department_id'); ?>
                <?php echo $form->dropDownList($model, 'department_id', $departmentList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'department_id'); ?>
            </div>
			<div class="row">
	            <?php echo $form->labelEx($model, 'app_key'); ?>
                <?php echo $form->textField($model, 'app_key',array('size'=>48,'maxlength'=>128,'empty'=>"不能为空")); ?>
                <?php echo $form->error($model, 'app_key'); ?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'secret_key'); ?>
                <?php echo $form->textField($model, 'secret_key',array('size'=>48,'maxlength'=>128,'empty'=>"不能为空")); ?>
                <?php echo $form->error($model, 'secret_key'); ?>
        	</div>
            <div class="row" id="PlatformAliexpressAccount_custom">
                <label for="PlatformAliexpressAccount_custom_url"></label>
                <input id="PlatformAliexpressAccount_custom_url" type="checkbox" style="float:left;">
                <span class="errorMessage">自定义返回地址</span>
            </div>
            <div class="row" id="PlatformAliexpressAccount_redirect" style="display:none;">
                <label for="PlatformAliexpressAccount_redirect_uri">Redirect URI</label>
                <input size="48" id="PlatformAliexpressAccount_redirect_uri" type="text" value="" class="textInput">
            </div>
            <div class="row" style="overflow:hidden;padding-left:220px;">
                <div class="button"><div class="buttonContent"><button type="button" onclick="addSave()">保存并跳转授权</button></div></div>
            </div>
            <div class="row" id="rowID" style="display:none;">
                <label for="PlatformAliexpressAccount_code">code</label>
                <input size="48" id="PlatformAliexpressAccount_code" type="text" class="textInput">
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
$("document").ready(function(){
    $("#PlatformAliexpressAccount_custom_url").click(function(){
        if($(this).attr("checked"))
        {
            $("#PlatformAliexpressAccount_redirect").css({'display':'block'});
        }else{
            $("#PlatformAliexpressAccount_redirect_uri").val('');
            $("#PlatformAliexpressAccount_redirect").css({'display':'none'});
        }
    })
});

    function addSave(){
        var email = $("#PlatformAliexpressAccount_email").val();
        if(email == ''){
            alertMsg.error('申请邮箱不能为空');
            return false;
        }

        var shortName = $("#PlatformAliexpressAccount_short_name").val();
        if(shortName == ''){
            alertMsg.error('账号简称不能为空');
            return false;
        }

        var departmentID = $("#PlatformAliexpressAccount_department_id").val();
        if(departmentID == ''){
            alertMsg.error('所属部门必须选择');
            return false;
        }

        var appKey = $("#PlatformAliexpressAccount_app_key").val();
        if(appKey == ''){
            alertMsg.error('appKey不能为空');
            return false;
        }

        var appSecret = $("#PlatformAliexpressAccount_secret_key").val();
        if(appSecret == ''){
            alertMsg.error('appSecret不能为空');
            return false;
        }

        var redirectUri = $("#PlatformAliexpressAccount_redirect_uri").val();

        var postData = {'email':email, 'short_name':shortName, 'department_id':departmentID, 'app_key':appKey, 'secret_key':appSecret, 'redirect_uri':redirectUri};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformaliexpressaccount/addsave')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                window.open(data.url);
                $("#rowID").css({'display':'block'});
                $("#PlatformAliexpressAccount_ID").val(data.aid);
                // alertMsg.correct(data.message);             
            } else {
                alertMsg.error(data.message);
            }
        }, 'json');
    }


    function getToken(){
        var code = $("#PlatformAliexpressAccount_code").val();
        if(code == ''){
            alertMsg.error('code不能为空');
            return false;
        }

        var id = $("#PlatformAliexpressAccount_ID").val();
        if(id == ''){
            alertMsg.error('账号ID不能为空');
            return false;
        }

        var postData = {'code':code, 'id':id};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformaliexpressaccount/tokensave')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                alertMsg.correct(data.message);             
            } else {
                alertMsg.error(data.message);
            }
        }, 'json');
    }
</script>