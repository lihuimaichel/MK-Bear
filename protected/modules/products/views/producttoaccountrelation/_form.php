<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style type="text/css">
<!-- 
.errorMessage{width:auto;}
-->
</style>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'producttoaccountForm',
        'enableAjaxValidation' => false,
        'enableClientValidation' => true,
         'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
         	'additionValidate'=>'js:checkInquire',
            'afterValidate'=>'js:afterValidate'
        ),
        'action' => Yii::app()->createUrl($this->route, array('id' => $model->id)),
        'htmlOptions' => array(
            'class' => 'pageForm',
		)
    ));
    ?>   
    <div class="pageFormContent" layoutH="65">
           <div class="row">
      	 <label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;部门:</label>              
           	  <?php echo $form->dropDownList($model, 'dept', $depList,array('empty' => Yii::t('system', 'Please Select'),'onchange' => 'getEmpLists(this)')); ?>
              <?php //UebModel::model('Hrms')->getDep($emp_dept)?>
              <?php echo $form->error($model, 'dept'); ?>

   	 		<label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;销售员:</label>   
           	  <?php echo $form->dropDownList($model, 'seller_user_id',array()); ?>
              <?php echo $form->error($model, 'seller_user_id'); ?>
              <label style="width:90px;">&nbsp;&nbsp;&nbsp;&nbsp;预计刊登时间:</label>  
              <input type="text" id="ready_publish_time" name="ready_publish_time" value="" datefmt="yyyy-MM-dd HH:mm:ss" class="date textInput">
        </div> 
    	      <div class="row">
             <label style="width:60px;">平台名称:</label>               
           	  <?php echo $form->dropDownList($model, 'platform_code',UebModel::model('Platform')->getUseStatusCode(),array('empty' => Yii::t('system', 'Please Select'),'onchange' => 'getAccounts(this)')); ?>
              <?php //UebModel::model('Hrms')->getDep($emp_dept)?>
              <?php echo $form->error($model, 'platform_code'); ?>
              
          		<label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;站点:</label> 
           	  <?php echo $form->dropDownList($model, 'site',array()); ?>
              <?php echo $form->error($model, 'site'); ?>

				<label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;账号:</label> 
           	  <?php echo $form->dropDownList($model, 'account_id',array()); ?>
              <?php echo $form->error($model, 'account_id'); ?>
        </div> 

    	<div class="row">
            <?php echo $this->renderPartial('_require', array( 'form' => $form, 'model' => $model,'key'=>0)); ?>                       
        </div>    
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', 'Save')?></button>
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
function getAccounts(obj){
	var sellerId = $("#producttoaccountForm #ProductToAccountRelation_seller_user_id").val();
	if(!sellerId){
		alert("请先选择销售员");return false;
	}
	var strSite ='<option value="">请选择</option>';
	var siteId = '';
	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformaccountbyidnew',{'platform':$(obj).val(), 'seller_id':sellerId},function(data){
			if(data != null){
				$.each(data,function(key,value){
					key = key.substring(1);
					strSite +='<option value="'+key+'">'+value+'</option>';
				});
			}
			$(obj).parent().find("select").eq(2).html(strSite);	
	   },'json');
			
	}else{
		$(obj).parent().find("select").eq(2).html(strSite);
	}

	var str ='<option value="">请选择</option>';
	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformsiteoffer',{'platform':$(obj).val()},function(data){
			if(data != null){
				 var data2 = [];
                $.each(data,function(key,value){
                    //str +='<option value="'+key+'">'+value+'</option>';
                        data2[key] = value;
                    });
                    data2.sort();
                    for (key2 in data2) {
                        str +='<option value="'+key2+'">'+data2[key2]+'</option>';
                    }

			}
			$(obj).parent().find("select").eq(1).html(str);	
			
		},'json');
	}else{
		
		$(obj).parent().find("select").eq(1).html(str);
	}
}
function getEmpLists(obj){
	
	var strEmp ='<option value="">请选择</option>';
	if($(obj).val()){
		$.post('/users/users/deptempuser',{'dept':$(obj).val()},function(data){
			
			$.each(data,function(key,value){
				strEmp +='<option value="'+key+'">'+value+'</option>';
			});
			$(obj).parent().find("select").eq(1).html(strEmp);	
		},'json');
	}else{
		$(obj).parent().find("select").eq(1).html(strEmp);
	}
}
	function checkInquire(key){
		var arrSku= $("#producttoaccountForm .skuInquire");

		var reg = /^(([1-9]\d*)|\d)?$/;
		var tbody = $.trim($('table tbody', $.pdialog.getCurrent()).html());
		if(tbody==''){
			alertMsg.info('<?php echo Yii::t('products','Please enter the sku');?>')
			return false;
		}
		
		platform = $("#producttoaccountForm #ProductToAccountRelation_platform_code").val();
		if(!platform){
			alertMsg.info('<?php echo Yii::t('warehouses','请选择平台');?>')
			return false;
		}
		return true;
		//$('#require_Submit').click();
	}
	
	$("#producttoaccountForm #ProductToAccountRelation_seller_user_id").change(function(){
		 $("#producttoaccountid").find('tbody').html('');
	}); 
	$("#producttoaccountForm #ProductToAccountRelation_account_id").change(function(){
		var platform = $("#ProductToAccountRelation_platform_code").val();
		var sellerId = $("#producttoaccountForm #ProductToAccountRelation_seller_user_id").val();
		var accountId = $("#producttoaccountForm #ProductToAccountRelation_account_id").val();
		$.post('/products/producttoaccountrelation/platformsiteaccountcheck',{'platform':platform,'sellerId':sellerId,'accountId':accountId},function(data){
			if(data.status == 0){
				alert("该账号不属于该销售员");return true;
			}
			
		},'json');
	}); 
</script>

