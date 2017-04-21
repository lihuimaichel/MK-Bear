<style>
.pageFormContent #domestic label, .pageFormContent #international label{
	width:auto;
}

.pageFormContent #domestic select,.pageFormContent #international select {
    float: none;
}

</style>
<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'getorderid-grid',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl("/aliexpress/aliexpressorder/getorderdetail"),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:auto;">
        	<div class="row">
        		<label for="EbayProductAttributeTemplate_site_id">账号</label>        		
        		<select name="account_id" id="EbayProductAttributeTemplate_site_id">
					<option value="">   请选择    </option>
					<?php foreach (AliexpressAccount::getIdNamePairs() as $key=>$val):?>
					<option value="<?php echo $key;?>">   <?php echo $val;?>    </option>
					<?php endforeach;?>
					
				</select>        		
			</div>
			
			<!--<div class="row">
	            <label for="EbayProductAttributeTemplate_dispatch_time_max">订单创建时间</label>	            
	            <input size="28" maxlength="128" onfocus="this.select();" name="create_time" id="EbayProductAttributeTemplate_dispatch_time_max" type="text" class="textInput date" datefmt="yyyy-MM-dd HH:mm:ss">	            
	        	<span>直接复制订单中创建时间即可</span>     
	        </div>-->
	        
	        <div class="row">
        		<label for="EbayProductAttributeTemplate_return_description">OrderIDS</label>        		
        		<input type="text" class="textInput" name="order_id" id="EbayProductAttributeTemplate_return_description"/>
        		<span>只能填一个订单</span>     		
        	</div>
        </div>
        <br/>
		<div class="pd5" style="height:auto;">
			<div class="row" style="margin-left:400px">
	                <div class="buttonActive">
                          <div class="buttonContent">  
                               <a class="saveBtn" onClick="saveInfo();" href="javascript:void(0)">拉取</a>
                          </div>
                   </div>
                   
                   <a href="<?php echo Yii::app()->createUrl('/aliexpress/aliexpressorder/getorderfilterids');?>" target="navTab" style="display:none;" class="display_list">速卖通订单拉取</a>
	          </div>
                        
        </div>

    </div>

    <?php $this->endWidget(); ?>
</div>

<script type="text/javascript">
//保存刊登数据
function saveInfo(){
	$.ajax({
			type: 'post',
			url: $('form#getorderid-grid a.display_list').attr('href'),
			data:$('form#getorderid-grid').serialize(),
			success:function(result){
				if(result.statusCode != '200'){
					alertMsg.error(result.message);
				}else{
					alertMsg.correct(result.message);
					//setTimeout("navTab.reload()", 1500);
				}
			},
			dataType:'json'
	});
}
</script>