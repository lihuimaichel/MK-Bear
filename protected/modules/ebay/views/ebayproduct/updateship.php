<?php Yii::app()->clientscript->scriptMap['jquery.js'] = false; ?>
<div class="pageContent"> 
<?php
    $form = $this->beginWidget('ActiveForm', array(
        'id' => 'updateshipform',
        'enableAjaxValidation' => false,  
        'enableClientValidation' => true,
        'clientOptions' => array(
            'validateOnSubmit' => true,
            'validateOnChange' => true,
            'validateOnType' => false,
            'afterValidate'=>'js:afterValidate',
        ),
        'action' => '/ebay/ebayproduct/batchupdateship',
        'htmlOptions' => array( 
            'class' => 'pageForm',         
        )
    ));
?>
<div class="pageFormContent" layoutH="56"> 
    <div class="pd5" style="height:120px;">
		<input type="hidden" name="ids" id="ids" value="<?php echo $ids; ?>"/>
        <div class="row">
            <strong>本地物流信息</strong>      
             <?php  echo $form->error($model,'shipping',array('style' => 'float: right;'));?>
        </div>
        <div class="row">
            <input type='button' onClick='addShippingService("domestic");' value='添加'/>
        </div>
        <div class="row" id="domestic" class="shippingservice">

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
<script type="text/javascript">
var servicelNum = 0;
var shippinginfo = <?php echo $shippingInfo; ?>;
function addShippingService(type, service,costtype,additionalcost, shipcost, additionalshipcost){
    
    var servicename = type=='international' ? '国际运输' : '本地运输';
    var max = type=='international' ? 5 : 4;
    var selectShipto = type=='international' ? true : false;

    if(service==undefined){ service = '';}
    if(costtype==undefined){ costtype = '';}
    if(additionalcost==undefined){ additionalcost = '0.05';}
    if(shipcost==undefined){ shipcost = 0;}
    if(additionalshipcost==undefined){ additionalshipcost = 0;}

    if($("#"+type+" table").size()>=max){
        alert(servicename+"选项不能超过"+max+"个");
        return;
    }
    servicelNum++;
    var html = "<table style='width:500px;margin-bottom:15px;border:1px solid #ccc;padding:5px;' class='tb_trbl'>"
                    +"<tr><td scope='col'></td></tr>"
                    +"<tr><td scope='col'>"
                        +"Services: "+getServices(type,service)
                    +"</td></tr>"
                    +"<tr><td scope='col'>"
                    +"Cost:"
                        +"<span>"
                        +"<input class='ebay_shipcost' name='shipcost["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+shipcost+"'>"
                        +"</span>"
                    +"</td></tr>"
                    +"<tr><td scope='col'>"
                        +"Additional:" 
                        +"<input name='additionalshipcost["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+additionalshipcost+"' " 
                        +"dataType='Double' Require='true' msg='必须输入数值' id='additionalcost"+servicelNum+"'> "
                        +"<span><input type='button' onClick='removeShippingService(this);' value='删除'/></span>"
                    +"</td></tr>"
                    +"<input type='hidden' name='shippingServices["+servicelNum+"]' value='"+type+"'/>"
                +"</table>";
    $("#"+type).append(html);
}
//删除一个运输方式
function removeShippingService(obj){
    $(obj).parent().parent().parent().parent().parent().remove();
}

//获取运输方式
function getServices(type,service){
    var services = type=='international' ? shippinginfo.InternationalServices : shippinginfo.DomesticServices;
    var html = "<select name='services["+servicelNum+"]' dataType='Require' msg='必选'>";
    html += "<option value=''>请选择</option>";
    try{
        $.each(services,function(i,item){
            html += "<option disabled value=''>"+i+" Service</option>";
            $.each(item,function(key,val){
                html += "<option value='"+key+"' "+(service==key?'selected':'')+">&nbsp;&nbsp;&nbsp;"+val+"&nbsp;</option>";
            });
        });
        html += "</select>";
    }catch(e){}
    return html;
}

</script>