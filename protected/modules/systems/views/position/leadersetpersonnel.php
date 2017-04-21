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
        <input type="hidden" name="departmentId" value="<?php echo $departmentId; ?>">
        <input type="hidden" name="userId" value="<?php echo $userId; ?>">
        <div class="row" style="display:none;">
            <?php echo $form->labelEx($model, 'name');?>
            <?php echo $form->textField($model, 'name', array('size' => '55'));?>
            <?php echo $form->error($model, 'name');?>
        </div>
        <div class="pd5" style="height:auto;overflow:hidden;">
            <div class="bg14 pdtb2 dot">
                <strong>所属组员</strong>           
            </div>
            <div class="row">
                <?php foreach ($selectPersonnelList as $k=>$v):?>
                    <span style="width: 120px;height:40px;line-height:24px;display:inline-block;">
                        <label style="float: right;width:80px;"><?php echo $v;?></label>
                    </span>
                <?php endforeach;?>
            </div>
            <div class="bg14 pdtb2 dot">
                <strong>请选择员工</strong>           
            </div>
			<div class="row">
                <?php foreach ($personnelList as $key=>$value):?>
                    <span style="width: 120px;height:40px;line-height:24px;display:inline-block;">
                        <input id="continents_<?php echo $key?>" type="checkbox" name="personnelIDs[]" value='<?php echo $key;?>'>
                        <label for="continents_<?php echo $key;?>" style="float: right;width:80px;"><?php echo $value;?></label>
                    </span>
                <?php endforeach;?>
            </div>
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent">                        
                        <button type="submit"><?php echo Yii::t('system', '添加')?></button>                     
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