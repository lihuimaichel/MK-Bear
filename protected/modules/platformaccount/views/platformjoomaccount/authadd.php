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
        <div class="pd5" style="height:200px;">
            <input id="PlatformJoomAccount_ID" value="<?php echo $model->id; ?>" type="hidden" class="textInput">
            <div class="row">
                <?php echo $form->labelEx($model, 'account'); ?>
                <?php echo $form->textField($model, 'account',array('size'=>48, 'disabled'=>'disabled')); ?>
                <?php echo $form->error($model, 'account'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'account_name'); ?>
	        	<?php echo $form->textField($model, 'account_name',array('size'=>48, 'disabled'=>'disabled')); ?>
	           	<?php echo $form->error($model, 'account_name'); ?>
        	</div>
            <div class="row" style="overflow:hidden;padding-left:220px;">
                <div class="button"><div class="buttonContent"><button type="button" onclick="authorization()">跳转授权页面</button></div></div>
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
    function authorization(){
        var id = $("#PlatformJoomAccount_ID").val();
        if(id == ''){
            alertMsg.error('账号ID不能为空');
            return false;
        }

        var postData = {'id':id};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformjoomaccount/reauthorization')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                window.open(data.url);
                $("#rowID").css({'display':'block'});
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