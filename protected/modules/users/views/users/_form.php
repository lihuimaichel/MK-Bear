<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
    <?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'userForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
    	'focus' => array($model, 'user_name'),
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:definedAfterValidate',
        ),
        //'action' => Yii::app()->createUrl($this->route).'/id/'.$model->id, 
        'htmlOptions' => array(        
            'class' => 'pageForm',         
           )
    ));
    ?>   
    <div class="pageFormContent" layoutH="58" >
        <div class="row">
            <?php echo $form->labelEx($model, 'user_name'); ?>
            <?php echo $form->textField($model, 'user_name', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'user_name'); ?>          
        </div>
        <div class="row">
        	<?php echo $form->labelEx($model, 'department_id'); ?>
            <div style=" float:left; display:block;  overflow:auto; width:190px; height:250px; border:solid 1px #CCC; line-height:21px; background:#FFF;"> 
                <?php echo $this->renderPartial('users.components.views.DeptTree2', array('class' => 'tree treeFolder', 'id' => 'depTreePanel2')); ?>                                            
            </div>
            <?php echo $form->error($model, 'department_id'); ?>
            <?php echo $form->hiddenField($model, 'department_id', array('size' => 38)); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'en_name'); ?>
            <?php echo $form->textField($model, 'en_name', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'en_name'); ?>
        </div>
        <?php if($model->isNewRecord){?>
        <div class="row">
            <?php echo $form->labelEx($model, 'user_password'); ?>
            <?php echo $form->passwordField($model, 'user_password', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'user_password'); ?>
        </div>
        <?php }?>
        <div class="row">
            <?php echo $form->labelEx($model, 'user_full_name'); ?>
            <?php echo $form->textField($model, 'user_full_name', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'user_full_name'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'user_email'); ?>
            <?php echo $form->textField($model, 'user_email', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'user_email'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'user_tel'); ?>
            <?php echo $form->textField($model, 'user_tel', array( 'size' => 38)); ?>
            <?php echo $form->error($model, 'user_tel'); ?>
        </div>
        <div class="row">
            <?php echo $form->labelEx($model, 'user_status'); ?>                 
            <?php echo $form->dropDownList($model, 'user_status', VHelper::getStatusConfig()); ?>
            <?php echo $form->error($model, 'user_status'); ?>          
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
/** 
 * @param {type} form
 * @param {type} data
 * @param {type} hasError
 * @returns {Boolean}
 */

function definedAfterValidate(form, data, hasError) { 
    if (hasError) {       
        return false;
    }  
    var $form = $(form);
//        roles = [];
//       $form.find('.checked').each(function() {
//           roles.push($(this).parent('div').find('a').attr('id'));          
//       });
//       if (roles.length > 0) {
//           roles.join()
//           $('#send_roles').val(roles);
//       } else {          
//           alertMsg.warn('<?php echo Yii::t('system', 'Please select the roles')?>');             
//           return false;
//       }
    var _submitFn = function() {
        $.ajax({
            type: form.method || 'POST',
            url: $form.attr("action"),
            data: $form.serializeArray(),
            dataType: "json",
            cache: false,
            success: function(json) {               
                ajaxCallback(json);
            },
            error: DWZ.ajaxError
        });
    }   
    _submitFn();
    return false;
}

function setValue(departmentId){
	$('#User_department_id',$.pdialog.getCurrent()).val(departmentId);
}

</script>


