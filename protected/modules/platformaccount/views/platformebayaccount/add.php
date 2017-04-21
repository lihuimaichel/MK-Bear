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
        'action' => Yii::app()->createUrl("/platformaccount/platformebayaccount/addsave"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
        <div class="pd5" style="height:160px;">
            <div class="row">
                <?php echo $form->labelEx($model, 'user_name'); ?>
                <?php echo $form->textField($model, 'user_name',array('size'=>48)); ?>
                <?php echo $form->error($model, 'user_name'); ?>
            </div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'short_name'); ?>
	        	<?php echo $form->textField($model, 'short_name',array('size'=>48)); ?>
	           	<?php echo $form->error($model, 'short_name'); ?>
        	</div>
            <div class="row">
                <?php echo $form->labelEx($model, 'developer_id'); ?>
                <?php echo $form->dropDownList($model, 'developer_id', $developerList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'developer_id'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'department_id'); ?>
                <?php echo $form->dropDownList($model, 'department_id', $departmentList, array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'department_id'); ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($model, 'store_site'); ?>
                <?php echo $form->dropDownList($model, 'store_site', $siteList ,array('empty'=>Yii::t('system', 'Please Select')));?>
                <?php echo $form->error($model, 'store_site'); ?>
            </div>
        </div>               
    </div>
    <div class="formBar">
        <ul>
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button  type="button" onclick="getToken()">保存跳转授权页面</button>                     
                    </div>
                </div>
            </li>              
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close">关闭</button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
    function getToken(){
        var userName = $("#PlatformEbayAccount_user_name").val();
        if(userName == ''){
            alertMsg.error('账号名称不能为空');
            return false;
        }

        var shortName = $("#PlatformEbayAccount_short_name").val();
        if(shortName == ''){
            alertMsg.error('账号简称不能为空');
            return false;
        }

        var departmentID = $("#PlatformEbayAccount_department_id").val();
        if(departmentID == ''){
            alertMsg.error('所属部门必须选择');
            return false;
        }

        var developerID = $("#PlatformEbayAccount_developer_id").val();
        // if(developerID == ''){
        //     alertMsg.error('开发者账号不能为空');
        //     return false;
        // }
        
        var storeSite = $("#PlatformEbayAccount_store_site").val();

        var postData = {'user_name':userName, 'short_name':shortName, 'department_id':departmentID, 'developer_id':developerID, 'store_site':storeSite};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformebayaccount/addsave')?>';
        $.post(url, postData, function(data){
            if (data.statusCode == '200') {
                window.open(data.url);
                alertMsg.confirm('授权是否完成', {okCall:function(){
                    getTokenurl = '<?php echo Yii::app()->createUrl('/platformaccount/platformebayaccount/tokensave')?>';
                    var params = {'accountId':data.id, 'sessionId':data.sessionId};
                    $.post(getTokenurl, params, function(data){
                        if(data.statusCode == 200){
                            alertMsg.correct(data.message);
                            return false;
                        }else{
                            alertMsg.error(data.message);
                        }
                    }, 'json');
                }, cancelCall : function(){
                    //取消复原
                }});             
            } else {
                alertMsg.error(data.message);
            }
        }, 'json');
    }
</script>