<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<style>
    #seller_share span{width: 92px;display: inline-block;}
</style>
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
        <div class="pd5" style="height:120px;">
            <div class="row">
                <label for="ebay_account_share">帐号</label>
                <select name="ebay_account" id="ebay_account_share">
                    <option value="">-选择帐号-</option>
                    <?php foreach ($accountList as $departId=>$department):?>
                    <option value="<?php echo $departId;?>"><?php echo $department;?></option>
                    <?php endforeach;?>
                </select>
            </div>

            <div class="row">
                <label for="share_time">共享有效期</label>
                <select name="share_time" id="share_time">
                    <option value="">-请选择-</option>
                    <option value="30">30天</option>
                    <option value="60">60天</option>
                    <option value="90">90天</option>
                </select>
            </div>

            <div class="row">
                <label for="ebay_department_share">部门</label>
                <select name="department_id" id="ebay_department_share">
                    <option value="">-选择部门-</option>
                    <?php if($departmentList):?>
                    <?php foreach ($departmentList as $departId=>$department):?>
                    <option value="<?php echo $departId;?>"><?php echo $department;?></option>
                    <?php endforeach;?>
                    <?php endif;?>
                    <?php echo $form->error($model, 'ebay_department_share'); ?>
                </select>
            </div>
            <div class="row" id="seller_share">
                
            </div>
            
        </div>
    </div>
    <div class="formBar">
        <ul>              
            <li>
                <div class="buttonActive">
                    <div class="buttonContent"> 
                        <input type="hidden" name="eaby_account_share" vaule="submit">                       
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
<script>
$('select[name="department_id"]').change(function(){
    var depart_id = $(this).val();
    $.ajax({
        type: 'get',
        url : '/ebay/ebayaccountshare/selectDepart/depart_id/'+depart_id,
        data: {},
        dataType:'json',
        success:function(result){
            if(result.statusCode != '200'){
                alertMsg.error(result.message);
            }else{
                var sellerhtml = '';
                var sellerList = result.departUser;
                $.each(sellerList, function(i, n){
                    sellerhtml += '<span><input type="checkbox" id="sell_'+n.id+'" name="ebay_seller_id[]" value="'+n.id+'">'+n.user_full_name+'</span>';
                }); 
                $('#seller_share').html(sellerhtml);
            }
        }
            
    });
})
</script>
