<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'updatelocationForm',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => '/ebay/ebayproduct/batchupdatelocation',
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
?>
<div class="pageFormContent" layoutH="56"> 
    <div class="bg14 pdtb2 dot">
         <strong>所选itemID操作</strong>           
    </div>
    <div class="pd5" style="height:120px;">
		<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
        <div class="row" id="country_div">
            <?php echo CHtml::label(Yii::t('ebay', 'country').'<span class="required">*</span>', "country"); ?>
            <select id="country" name="country">
                <option value="">请选择</option>
                <?php foreach($countryArr as $country => $location):?>
                <option value="<?php echo $country ?>"><?php echo $country ?></option>
                <?php endforeach;?>
            </select>
            <?php  echo $form->error($model,'country',array('style' => 'float: right;'));?>
        </div>
        <div class="row" id="location_div">
            <?php echo CHtml::label(Yii::t('ebay', 'location').'<span class="required">*</span>', "dispatchtime_div"); ?>
            <select id="location" name="location">
                <option value="">请选择</option>
                <?php foreach($countryArr as $country => $location):?>
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
var countrylist = <?php echo $countryList?>;
$(function(){
    var updatelocationForm = document.getElementById('updatelocationForm');
    $('#country',updatelocationForm).change(function(){
        var country = $(this).val();
        var locations = countrylist[country];
        $('#location',updatelocationForm).remove();
        var html = '<select id="location" name="location"><option value="">请选择</option>';
        $.each(locations,function(i,n){
            html += '<option value="'+n+'">'+n+'</option>';
        });
        html += '</select>';
        $('#location_div',updatelocationForm).append(html);
    });
});

</script>