<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style type="text/css">
<!-- 
.errorMessage{width:auto;}
-->
</style>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'producttosellerForm',
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
              <?php echo $form->labelEx($model, 'MarketersManager_emp_dept'); ?>                
           	  <?php echo $form->dropDownList($model, 'MarketersManager_emp_dept',UebModel::model('Department')->getMarketsDepartmentInfo(),array('empty' => Yii::t('system', 'Please Select'),'onchange' => 'getEmpList(this)')); ?>
              <?php //UebModel::model('Hrms')->getDep($emp_dept)?>
              <?php echo $form->error($model, 'MarketersManager_emp_dept'); ?>
       <!--   </div>
        <div class="row">-->
              <?php
              echo $form->labelEx($model, 'seller_id'); ?>
           	  <?php echo $form->dropDownList($model, 'seller_id',array()); ?>
              <?php echo $form->error($model, 'seller_id'); ?>
        </div> 
    	<div class="row">
    		<?php //echo CHtml::hiddenField('is_tc', $is_tc); ?>
    		<?php //echo $form->hiddenField($model, 'ac', array('value' => 'require')); ?>
    		<?php //echo $form->error($model, 'ac'); ?>
            <?php echo $this->renderPartial('_require', array( 'form' => $form, 'model' => $model)); ?>                       
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
	function checkInquire(key){
		var arrSku= $("#producttosellerForm .skuInquire");
		var skuEmpty = false;
		var numEmpty = false;
		var warehouseEmpty = true;
		var tcTypeEmpty = false;
		var isRepeat = false;
		var skuArr = [];
		var reg = /^(([1-9]\d*)|\d)?$/;
		var tbody = $.trim($('table tbody', $.pdialog.getCurrent()).html());
		if(tbody==''){
			alertMsg.info('<?php echo Yii::t('products','Please enter the sku');?>')
			return false;
		}
		arrSku.each(function(){
			curId = this.id.substr(4);
			var curNum = $.trim($('#reality_num_'+curId, $.pdialog.getCurrent()).val());
			var warehouseId = $.trim($('#warehouse_id_'+curId, $.pdialog.getCurrent()).val());
			var tctype = $.trim($('#tc_type_'+curId, $.pdialog.getCurrent()).val());
			var curSku = $(this).val();
			if( curSku == ''){
				skuEmpty = true;
			}
		});
		if(skuEmpty){
			alertMsg.info('<?php echo Yii::t('products','Please enter the sku');?>')
			return false;
		}
		if(isRepeat){
			alertMsg.info('SKU:'+$.regional.products.msg.cannotAddAgain);
			return false;
		}
		if(numEmpty){
			alertMsg.info($.regional.purchase.msg.purchaseNumRequire);
			return false;
		}
		warehouseEmpty = $("#producttosellerForm #ProductToSellerRelation_seller_id").val();
		if(!warehouseEmpty){
			alertMsg.info('<?php echo Yii::t('warehouses','请选择销售人员');?>')
			return false;
		}

		return true;
		//$('#require_Submit').click();
	}
	function getEmpList(obj){
		
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
</script>

