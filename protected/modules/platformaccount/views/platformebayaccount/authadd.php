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
        // 'action' => '',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:180px;">
        	<div class="row">
	            <?php echo $form->labelEx($model, 'short_name'); ?>
	        	<?php echo $form->textField($model, 'short_name',array('size'=>28, 'disabled'=>'disabled')); ?>
	           	<?php echo $form->error($model, 'short_name'); ?>
        	</div>
            <div class="row">
                <label for="PlatformEbayAccount_developer_id">开发者账号</label>
                <select name="PlatformEbayAccount[developer_id]" id="PlatformEbayAccount_developer_id">
                    <option value="">自动选择</option>
                <?php foreach ($developerList as $key=>$val): ?>
                    <option value="<?php echo $key; ?>"><?php echo $val; ?></option>
                <?php endforeach;?>
                </select>
            </div>
        </div>               
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button  type="button" onclick="authorization()">保存跳转授权页面</button>                     
                    </div>
                </div>
            </li>
            <li>
                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
            </li>
        </ul>
    </div>
    <?php $this->endWidget(); ?>
</div>
<script type="text/javascript">
    function authorization(){
        var developerID = $("#PlatformEbayAccount_developer_id").val();
        var id = '<?php echo $model->id; ?>';
        var postData = {'developer_id':developerID, 'id':id};
        var url = '<?php echo Yii::app()->createUrl('/platformaccount/platformebayaccount/reauthorization')?>';
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