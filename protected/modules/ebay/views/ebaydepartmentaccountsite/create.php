<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'addebaydepartmentForm',
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
    <div class="bg14 pdtb2 dot">
         <strong>添加部门帐号关系</strong>           
    </div>
    <div class="pd5" id="account_div" style="height:120px;">
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '部门').'<span class="required">*</span>', "department_id"); ?>
            <select id="department_id" name="department_id">
                <option value="">请选择</option>
                <?php foreach ($departmentLists as $key => $value) :?>
                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                <?php endforeach;?>
            </select>
            <?php  echo $form->error($model,'department_id',array('style' => 'float: right;'));?>
        </div>

        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '账号').'<span class="required">*</span>', "account_id"); ?>
            <select id="account_id" name="account_id">
                <option value="">请选择</option>
                <?php foreach($accountArr as $id=>$name):?>
                <option value="<?php echo $id ?>"><?php echo $name ?></option>
                <?php endforeach;?>
            </select>
            <?php  echo $form->error($model,'account_id',array('style' => 'float: right;'));?>
        </div>

        <div class="row" id="site_div">
            <?php echo CHtml::label(Yii::t('ebay', '站点').'<span class="required">*</span>', "site_id"); ?>
            <?php foreach($siteArr as $id=>$site):?>
            <input type="checkbox" name="site_id[]" id="site_id_<?php echo $id;?>" value="<?php echo $id;?>" style="float: left;"  />
            <label for="site_id_<?php echo $id;?>" style="width: inherit;"><?php echo $site;?></label>
            <?php endforeach;?>
        </div>
    </div>
</div>
<div class="formBar">
    <ul>              
        <li>
            <div class="buttonActive">
                <div class="buttonContent">          
                    <button type="submit"><?php echo Yii::t('system', '提交')?></button>                     
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