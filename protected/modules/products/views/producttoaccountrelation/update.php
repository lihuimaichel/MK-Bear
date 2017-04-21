<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'ProductToAccountRelation',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	//'focus' => array($mod, ''),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route, array('id'=>$model['id'],'platform_code'=>$model['platform_code'])), 
        'htmlOptions' => array(        
            'class' 	=> 'pageForm',
        	'target'	=> 'dialog',
        	'rel'		=>	'ProductToAccountRelation',
        	'onsubmit'	=>	'return validateCallback(this, dialogAjaxDone)'         
        )
    ));
 
   // echo '<pre>';print_r($model);die;
    ?>   
    <div class="pageFormContent" layoutH="56">   
              <div class="row">
              <?php echo CHtml::hiddenField("sku", $model['sku'], array('class'=>'textInput skuInquire','id'=>'sku','size' => 10,'readonly'=>'readonly')); ?>
             
             </div> 
              <!--    	      <div class="row">
             <label style="width:60px;">平台名称:</label> 
             <?php //echo CHtml::dropDownList("platform_code",$model['platform_code'],UebModel::model('Platform')->getPlatformList(), array('onchange' => 'getAccounts(this)','class'=>'textInput','id'=>'platform_code','empty' =>Yii::t('system','Please Select')))?>              

               </div>


       
             <div class="row">
          		<label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;站点:</label> 
          		 <?php //echo CHtml::dropDownList("site",$model['site'], UebModel::model('ProductToAccountRelation')->getOfferSiteByPlatfromCode($model['platform_code']));?>              
           
         
             </div> 
             <div class="row">

				<label style="width:60px;">&nbsp;&nbsp;&nbsp;&nbsp;账号:</label> 
				 <?php //echo CHtml::dropDownList("account_id",$model['account_id'],UebModel::model('ProductToAccountRelation')->getPlatformAccountById($model['platform_code']))?>              
      
             </div> -->
              <div class="row">

				<label style="width:90px;">&nbsp;&nbsp;&nbsp;&nbsp;预计刊登时间:</label> 
				<input type="text" id="ready_publish_time" name="ready_publish_time" value="<?php echo $model['ready_publish_time'];?>" datefmt="yyyy-MM-dd HH:mm:ss" class="date textInput">
				     
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

<script>
function getEmp(obj){
	var strEmp ='<option value="">请选择</option>';
	if($(obj).val()){
		$.post('/users/users/deptempuser',{'dept':$(obj).val()},function(data){
			$.each(data,function(key,value){
				strEmp +='<option value="'+key+'">'+value+'</option>';
			});
			$(obj).parent().next().find("select").html(strEmp);	
		},'json');
	}else{
		$(obj).parent().next().find("select").html(strEmp);
	}
}
function getAccounts(obj){
	
	var strSite ='<option value="">请选择</option>';

	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformaccountbyid',{'platform':$(obj).val()},function(data){
			if(data != null){
				$.each(data,function(key,value){
					strSite +='<option value="'+key+'">'+value+'</option>';
				});
			}
			$(obj).parent().next().next().find("select").html(strSite);	
	   },'json');
			
	}else{
		$(obj).parent().next().next().find("select").html(strSite);
	}

	var str ='<option value="">请选择</option>';
	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformsiteoffer',{'platform':$(obj).val()},function(data){
			if(data != null){
				$.each(data,function(key,value){
					
					str +='<option value="'+key+'">'+value+'</option>';
				});
			}
			$(obj).parent().next().find("select").html(str);	
			
		},'json');
	}else{
		
		$(obj).parent().next().find("select").html(str);
	}
}
    </script>
