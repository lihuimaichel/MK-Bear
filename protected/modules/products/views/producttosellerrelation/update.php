<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'productTosellerForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, ''),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => Yii::app()->createUrl($this->route, array('id'=>$model->id)), 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));

    ?>   
    <div class="pageFormContent" layoutH="56">   
        <div class="dot7 pd5" style="height:350px;">
             <div class="row">
                <?php echo $form->labelEx($model, 'sku'); ?>                 
                <?php echo  $form->textField($model,'sku',array('readonly'=>'readonly')); ?>
                <?php echo $form->error($model, 'sku'); ?>                    
             </div> 
             <div class="row">
                <?php echo $form->labelEx($model, 'MarketersManager_emp_dept'); ?>                 
                <?php //echo $form->dropDownList($model,'MarketersManager_emp_dept', UebModel::model('Department')->getMarketsDepartmentInfo($user->department_id),array('cols'=> 70, 'rows' => 5)); ?>
               	  <?php echo $form->dropDownList($model, 'MarketersManager_emp_dept',UebModel::model('Department')->getMarketsDepartmentInfo($model->MarketersManager_emp_dept),array('empty' => Yii::t('system', 'Please Select'),'onchange' => 'getEmp(this)')); ?>
                <?php echo $form->error($model, 'MarketersManager_emp_dept'); ?>                 
             </div> 
             <div class="row">
                <?php echo $form->labelEx($model, 'seller_id'); ?>                 
                <?php echo $form->dropDownList($model,'seller_id',UebModel::model('User')->getEmpByDept($model->MarketersManager_emp_dept),isset($model->seller_id)?array('options'=>array($model->seller_id=>array('selected'=>'selected'))):''); ?>
                <?php echo $form->error($model, 'seller_id'); ?>                 
             </div> 
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
    </script>
