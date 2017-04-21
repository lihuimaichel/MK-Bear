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
    	<div class="bg14 pdtb2 dot">
	         <strong>目前所属账号</strong>           
	    </div>
        <div class="pd5" style="height:180px;">
        	<div class="row">
        		<label>注意：</label>
        		<span style="color: red;">此操作考虑性能问题，并不会一次性把所属账号的sku都设置完成，所以你可能需要多次重复操作！</span>
        	</div>
            <div class="row">
                <label for="old_site_id">站点</label>
                <select name="old_site_id" id="old_site_id">
                        <option value="-1">-选择站点-</option>
                        <?php if($siteList):?>
                        <?php foreach ($siteList as $id=>$accout):?>
                        <option value="<?php echo $id;?>"><?php echo $accout;?></option>
                        <?php endforeach;?>
                        <?php endif;?>
                </select>
            </div>
        	<div class="row">
        		<label for="old_account_id">账号</label>
        		<select name="old_account_id" id="old_account_id">
        				<option value="0">-选择账号-</option>
        				<?php if($accountList):?>
        				<?php foreach ($accountList as $id=>$accout):?>
        				<option value="<?php echo $id;?>"><?php echo $accout;?></option>
        				<?php endforeach;?>
        				<?php endif;?>
        		</select>
        	</div>
        </div>
        
        <div class="bg14 pdtb2 dot">
	         <strong>改为所属销售人员</strong>           
	    </div>
        <div class="pd5" style="height:120px;">
            <div class="row">
                <label for="ebay_department_sell">部门</label>
                <select name="ebay_department_sell" id="ebay_department_sell">
                    <option value="">-选择部门-</option>
                    <?php if($departmentList):?>
                    <?php foreach ($departmentList as $departId=>$department):?>
                    <option value="<?php echo $departId;?>"><?php echo $department;?></option>
                    <?php endforeach;?>
                    <?php endif;?>
                </select>
            </div>
			<div class="row">
                <?php  echo $form->labelEx($model,'seller_id');?>
                <?php  echo $form->dropDownList($model,'seller_id',$sellerList,array('class'=>'ebay_sell_id'));?>
                <?php  echo $form->error($model,'seller_id',array('style' => 'float: right;'));?>
            </div>
        </div>
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
<script>
$('select[name="ebay_department_sell"]').change(function(){
    var depart_id = $(this).val();
    $.ajax({
        type: 'get',
        url : '/ebay/ebayproductsellerrelation/selectDepart/depart_id/'+depart_id,
        data: {},
        dataType:'json',
        success:function(result){
            if(result.statusCode != '200'){
                alertMsg.error(result.message);
            }else{
                var sellerhtml = '';
                var sellerList = result.departUser;
                $.each(sellerList, function(i, n){
                    sellerhtml += "<option value='"+i+"'>"+n+"</option>";
                }); 
                $('.ebay_sell_id').html(sellerhtml);
            }
        }
            
    });
})
</script>