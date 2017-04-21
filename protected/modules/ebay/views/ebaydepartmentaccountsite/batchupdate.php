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
        'action' => Yii::app()->createUrl($this->route),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
    <div class="pageFormContent" layoutH="56">   
        <div class="pd5" >
			<div class="row">
                <label for="u_department_id">部门</label>
                <select name="department_id" id="u_department_id">
                    <option value="">请选择部门</option>
                    <?php foreach ($departmentLists as $key => $value) { ?>
                        <option value="<?php echo $key;?>"><?php echo $value;?></option>
                    <?php }?>
                </select>
                <?php echo $form->error($model, 'department_id'); ?> 
            </div>
        </div>
        <input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '更改')?></button>                     
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