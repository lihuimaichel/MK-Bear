<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'updatedispatchtimeForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => '/ebay/ebayproduct/batchupdatedispatchtime',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
<div class="pageFormContent" layoutH="56"> 
    <div class="pd5" id="account_div" style="height:120px;">
		<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '操作方式').'<span class="required">*</span>', "account_id"); ?>
            <label for="type_1"><input type="radio" name="type" id="type_1" value="1" checked="checked">按账号站点</label>
            <label for="type_2"><input type="radio" name="type" id="type_2" value="2">按所选item</label>
        </div>
        <div class="row" id="accountid_div">
            <?php echo CHtml::label(Yii::t('ebay', '账号').'<span class="required">*</span>', "account_id"); ?>
            <select id="account_id" name="account_id">
                <option value="">请选择</option>
                <?php foreach($accountArr as $id=>$name):?>
                <option value="<?php echo $id ?>"><?php echo $name ?></option>
                <?php endforeach;?>
            </select>
            <?php  echo $form->error($model,'account_id',array('style' => 'float: right;'));?>
        </div>
        <div class="row" id="siteid_div">
            <?php echo CHtml::label(Yii::t('ebay', '站点').'<span class="required">*</span>', "site_id"); ?>
            <select id="site_id" name="site_id">
                <option value="">请选择</option>
                <?php foreach($siteArr as $id=>$name):?>
                <option value="<?php echo $id ?>"><?php echo $name ?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="row" id="dispatchtime_div">
            <?php echo CHtml::label(Yii::t('ebay', '备货天数').'<span class="required">*</span>', "dispatchtime_div"); ?>
            <input type="text" name="dispatchtime" id="dispatchtime" value="1" size="10">天
        </div> 
        <div class="row" id="location_div">
            <?php echo CHtml::label(Yii::t('ebay', 'location'), "location_div"); ?>
            <select id="location" name="location">
                <option value="">请选择</option>
                <?php foreach($locations as $location):?>
                <option value="<?php echo $location ?>"><?php echo $location ?></option>
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

<script type="text/javascript">
    
$(function(){
    var updatedispatchtimeForm = document.getElementById('updatedispatchtimeForm');
    $('input[name="type"]',updatedispatchtimeForm).change(function(){
        var divs = $('#accountid_div,#siteid_div',updatedispatchtimeForm);
        if (this.value == 1) {
            divs.show();
        } else {
            divs.hide();
        }
    });
});

</script>