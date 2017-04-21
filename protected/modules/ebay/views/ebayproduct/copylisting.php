<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'copylistingForm',
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
         <strong>所选itemID操作</strong>           
    </div>
    <div class="pd5" id="account_div" style="height:120px;">
		<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '目标账号').'<span class="required">*</span>', "account_id"); ?>
            <select id="account_id" name="account_id">
                <option value="">请选择</option>
                <?php foreach($accountArr as $id=>$name):?>
                <option value="<?php echo $id ?>"><?php echo $name ?></option>
                <?php endforeach;?>
            </select>
            <?php  echo $form->error($model,'account_id',array('style' => 'float: right;'));?>
        </div>
        <div class="row" id="site_div">
            <?php echo CHtml::label(Yii::t('ebay', '目标站点').'<span class="required">*</span>', "site_id"); ?>
            <?php foreach($siteArr as $id=>$site):?>
            <input type="radio" name="site_id" id="site_id_<?php echo $id;?>" value="<?php echo $id;?>" style="float: left;"  />
            <label for="site_id_<?php echo $id;?>" style="width: inherit;"><?php echo $site;?></label>
            <?php endforeach;?>
        </div>
        <div class="row" id="duration_div">
            <?php echo CHtml::label(Yii::t('ebay', '刊登时长'), "duration"); ?>
            <select id="duration" name="duration">
                <option value="">请选择</option>
                <?php foreach($listingDurations as $key=>$day):?>
                <option value="<?php echo $key;?>"><?php echo $day;?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '处理方式').'<span class="required">*</span>', "handle_type"); ?>
            <input type="radio" name="handle_type" id="handle_type_1" value="1" style="float: left;"  />
            <label for="handle_type_1" style="width: inherit;">实时处理</label>
            <input type="radio" name="handle_type" id="handle_type_2" value="2" checked style="float: left;"  />
            <label for="handle_type_2" style="width: inherit;">异步处理</label>            
        </div>      
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '折扣率'), "discount"); ?>
            <input type="text" name="discount_rate" />
        </div>  
        <div class="row">
            <?php echo CHtml::label(Yii::t('ebay', '刊登类型'), "listing_type"); ?>
            <input type="radio" name="listing_type" id="listing_type_1" value="1" style="float: left;"  />
            <label for="listing_type_1" style="width: inherit;">拍卖</label>
            <input type="radio" name="listing_type" id="listing_type_2" value="2" style="float: left;"  />
            <label for="listing_type_2" style="width: inherit;padding-right:22px;">一口价</label>   
            <span style="display:block;line-height:16px;margin:4px 0;color:red;">多属性忽略此项</span> 
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
    //加载配置参数
    var copylistingForm = document.getElementById('copylistingForm');
    $('#account_id,input[name="site_id"]', copylistingForm).change(function(){
        var account_id = $('#account_id',copylistingForm).val();
        var site_id = $('input[name="site_id"]:checked', copylistingForm).val();
        if (account_id == '' || site_id == undefined) {
            return;
        }
        $.ajax({
                type:'post',
                url:'ebay/ebayproduct/getconfigtype',
                data:{ account_id:account_id, site_id:site_id },
                success:function(result){
                    $('#configtype_div').remove();
                    var html = '<div class="row" id="configtype_div"><label for="configtype_div">配置参数<span class="required">*</span></label>';
                    if (result.errCode == 200) {
                        $.each(result.data,function(i,n){
                            var checked = n.checked == 1 ? 'checked="checked"' : '';
                            html += '<input id="configtype_'+i+'" name="configtype" value="'+i+'" style="float: left;" type="radio" '+checked+'><label for="configtype_'+i+'" style="width: inherit;">'+n.name+'</label>';
                        });
                    }
                    $('#site_div').after(html);
                },
                dataType:'json'
        });
    });

</script>