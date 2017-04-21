<style>
.pageFormContent #domestic label, .pageFormContent #international label{
	width:auto;
}

.pageFormContent #domestic select,.pageFormContent #international select {
    float: none;
}
#locations{
	display:none;
}
</style>
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
        'action' => Yii::app()->createUrl($this->route,array('id'=>$model->id)),
        'htmlOptions' => array(        
            'class' => 'pageForm',         
        )
    ));
    ?>
    <input type="hidden" name="scountry" id="scountry" value="<?php echo implode(',', $specialCountry);?>">
    <div class="pageFormContent" layoutH="56"> 
    	<div class="bg14 pdtb2 dot">
	         <strong><?php echo Yii::t('system', 'Basic Information')?></strong>           
	    </div>
        <div class="pd5" style="height:auto;">
        	<div class="row">
        		<?php echo $form->labelEx($model, 'site_id');?>
        		<?php echo $form->dropDownList($model, 'site_id', EbaySite::getSiteList(), array('readonly'=>true));?>
        		<?php echo $form->error($model,'site_id');?>
        		<span></span>
        	</div>
            <div class="row">
	            <?php echo $form->labelEx($model, 'name'); ?>
	            <?php echo $form->textField($model, 'name',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'name'); ?>
	            <span>模板名称跟刊登提交的参数无关联</span>
        	</div>
        	
        	<div class="row">
        		<?php echo $form->labelEx($model, 'config_type');?>
        		<?php echo $form->dropDownList($model, 'config_type', EbayProductAdd::getConfigType());?>
        		<?php echo $form->error($model,'config_type');?>
        	</div>
        	
        	
        	<div class="row">
        		<?php echo $form->labelEx($model, 'abroad_warehouse');?>
        		<?php echo $form->dropDownList($model, 'abroad_warehouse', Warehouse::model()->getWarehousePairs(),  array('empty'=>array(''=>"默认")));?>
        		<?php echo $form->error($model,'abroad_warehouse');?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'condition_id'); ?>
	            <?php echo $form->textField($model, 'condition_id',array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'condition_id'); ?>
	            <span>如果为NEW,则填1000</span>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'dispatch_time_max'); ?>
	            <?php echo $form->textField($model, 'dispatch_time_max', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'dispatch_time_max'); ?>
	            <span>派发时间(填数字)</span>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'country'); ?>
	            <?php echo $form->textField($model, 'country', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'country'); ?>
	            
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'location'); ?>
	            <?php echo $form->textField($model, 'location', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'location'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'listing_duration'); ?>
	            <?php echo $form->textField($model, 'listing_duration', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'listing_duration'); ?>
	            <span>持续刊登则为 GTC,否则填写:Days_1,Days_2,Days_3</span>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'listing_duration_auction'); ?>
	            <?php echo $form->textField($model, 'listing_duration_auction', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'listing_duration_auction'); ?>
	            <span>持续刊登则为 GTC,否则填写:Days_1,Days_2,Days_3</span>
        	</div>
        	
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'auction_price'); ?>
	            <?php echo $form->textField($model, 'auction_price', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'auction_price'); ?>
	            <span>填写拍卖起价</span>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'auction_hotsell_price'); ?>
	            <?php echo $form->textField($model, 'auction_hotsell_price', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'auction_hotsell_price'); ?>
	            <span>填写热销拍卖起价</span>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'time_zone'); ?>
	            <?php echo $form->dropDownList($model, 'time_zone_prefix', array('+'=>'+', '-'=>'-'));?>
	            <?php echo $form->textField($model, 'time_zone', array('size'=>28,'maxlength'=>128)); ?>
	            <?php echo $form->error($model, 'time_zone'); ?>
        	</div>
        	
        </div>
        
        <br/>
        <br/>
        <br/>
        <div class="bg14 pdtb2 dot">
	         <strong>退货策略</strong>      
	    </div>
	    <div class="pd5" style="height:auto;">
	    	<div class="row">
	            <?php echo $form->labelEx($model, 'returns_accepted_option'); ?>
	            <?php echo $form->dropDownList($model, 'returns_accepted_option', isset($returnPolicy['ReturnsAccepted']) ? $returnPolicy['ReturnsAccepted'] : array(), array('empty'=>Yii::t('system', 'Please Select'))); ?>
	            <?php echo $form->error($model, 'returns_accepted_option'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'refund_option'); ?>
	            <?php echo $form->dropDownList($model, 'refund_option', isset($returnPolicy['RefundOptions']) ? $returnPolicy['RefundOptions'] : array(), array('empty'=>Yii::t('system', 'Please Select'))); ?>
	            <?php echo $form->error($model, 'refund_option'); ?>
        	</div>
        	<div class="row">
	            <?php echo $form->labelEx($model, 'returns_within_option'); ?>
	            <?php echo $form->dropDownList($model, 'returns_within_option', isset($returnPolicy['ReturnsWithin']) ? $returnPolicy['ReturnsWithin'] : array(), array('empty'=>Yii::t('system', 'Please Select'))); ?>
	            <?php echo $form->error($model, 'returns_within_option'); ?>
        	</div>
        	
        	<div class="row">
	            <?php echo $form->labelEx($model, 'shipping_cost_option'); ?>
	            <?php echo $form->dropDownList($model, 'shipping_cost_option', isset($returnPolicy['ShippingCostPaidBy']) ? $returnPolicy['ShippingCostPaidBy'] : array(), array('empty'=>Yii::t('system', 'Please Select'))); ?>
	            <?php echo $form->error($model, 'shipping_cost_option'); ?>
        	</div>
        	
	    	<div class="row">
        		<?php echo $form->labelEx($model, 'return_description');?>
        		<?php echo $form->textArea($model, 'return_description', array('style'=>'width:400px;height:60px;', 'class'=>''));?>
        		<?php echo $form->error($model,'return_description');?>
        	</div>
	    </div>
	    <br/>
        <br/>
        <br/>
        <br/>
        <br/>
        <br/>
        <div class="bg14 pdtb2 dot">
	         <strong>本地物流信息</strong>      
	    </div>
        <div class="pd5" style="height:auto;">
        	<div class="row">
        		<input type='button' onClick='addShippingService("domestic");' value='添加'/>
        	</div>
        	<div class="row" id="domestic" class="shippingservice">
	            	
			</div>
        </div>
       	<br/>
        <br/>
        <br/>
        <br/>
        <br/>
        <br/>
        <div class="bg14 pdtb2 dot">
	         <strong>国际物流信息</strong>      
	    </div>
        <div class="pd5" style="height:auto;">
        	<div class="row">
        		<input type='button' onClick='addShippingService("international");' value='添加'/>
        	</div>
        	<div class="row" id="international" class="shippingservice">
	            	
			</div>
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
var _keywords = $('input[name=search_keywords]').val();
$(function(){
	KindEditor.create('textarea.ebayProductDescriptionTemplate',{
		allowFileManager: true,
		width: '80%',
		height: '120',
		afterCreate : function() {
	    	this.sync();
	    },
	    afterBlur:function(){
	        this.sync();
	    },
	});
});


var shippinginfo = <?php echo json_encode($shippingInfo);?>;
var servicelNum = 0;//运输序号
var locationNum = 0;//国家及地区序号

//获取国家和地区
function getLocations(shiptolocation){
	var locations = shiptolocation.split(',');
	var html = "";
	try{
		$.each(shippinginfo.ShippingLocationDetails,function(i,item){
			if(item=='None' || item=='Worldwide'){
				return;
			}
			var checked = $.inArray(i,locations)!=-1 ? 'checked' : '';
			locationNum++;
			html += "<li>"
						+"<label><input type='checkbox' class='location' "+checked+" name='locations["+servicelNum+"]["+locationNum+"]' title='"+item+"' value='"+i+"'>"
						+""+item+"</label>"	
					+"</li>";
		});
	}catch(e){
	}
	return html;
}
function getShiptoInfo(shiptolocation){
	var html = "<tr><td>"
		+"SHIP TO:"
		+"<select name='shoptos["+servicelNum+"]' onchange='selectLocation(this);'>"
			+"<option value='Worldwide'>Worldwide</option>"
			+"<option value='' "+(shiptolocation!='Worldwide' ? 'selected':'')+">Choose custom location</option>"
		+"</select> "
		+"<ul id='locations' "+(shiptolocation!='Worldwide'?'style=\"display:block\"':'')+">"+getLocations(shiptolocation)+"</ul>"
	+"</td></tr>"
	return html;
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
		
//添加运输
//service,costtype,additionalcost,shiptolocation
function addShippingService(type,service,costtype,additionalcost,shiptolocation){
//Nick 2013-8-27添加特殊国家
	 var insert = '';
	 var scountry = $("#scountry").val();
	 var countryArr = scountry.split(',');
	 var length = countryArr.length;
	 for(var i=0;i < length;i++){
	     insert += "<option value='"+countryArr[i]+"' "+(costtype== countryArr[i] ?'selected':'')+">"+countryArr[i]+"</option>";
	 }
	 
	var servicename = type=='international' ? '国际运输' : '本地运输';
	var max = type=='international' ? 5 : 4;
	var selectShipto = type=='international' ? true : false;

	if(service==undefined){ service = '';}
	if(costtype==undefined){ costtype = '';}
	if(additionalcost==undefined){ additionalcost = '0.05';}
	if(shiptolocation==undefined){ shiptolocation = 'Worldwide';}
	
	if($("#"+type+" table").size()>=max){
		alert(servicename+"选项不能超过"+max+"个");
		return;
	}
	servicelNum++;
	 var html = "<table style='width:600px;margin-bottom:15px;border:1px solid #ccc;padding:5px;' class='tb_trbl'>"
		 			+"<tr><td scope='col'></td></tr>"
		  			+ (selectShipto ? getShiptoInfo(shiptolocation) : '')
  					+"<tr><td scope='col'>"
  						+"Services: "+getServices(type,service)
    				+"</td></tr>"
    				+"<tr><td scope='col'>"
    					+"Cost:"
    					+"<span>"
    					+"<select name='costtypes["+servicelNum+"]' onchange='changeReferValue(this)'>"
    						+"<option value='1' "+(costtype=='1'?'selected':'')+">FREE SHIPPING</option>"
	    						+"<option value='2' "+(costtype=='2'?'selected':'')+">EUB</option>" 
	                   		 +insert+
	                    "</select> "
						+"</span>"
					+"</td></tr>"
					+"<tr><td scope='col'>"
						+"Additional = Cost-" 
						+"<input name='additionalcosts["+servicelNum+"]' type='text' maxlength='10' size='10' value='"+additionalcost+"' " 
						+"dataType='Double' Require='true' msg='必须输入数值' id='additionalcost"+servicelNum+"'> "
						+"<span><input type='button' onClick='removeShippingService(this);' value='删除'/></span>"
					+"</td></tr>"
					+"<input type='hidden' name='shippingServices["+servicelNum+"]' value='"+type+"'/>"
				+"</table>";
	$("#"+type).append(html);
}
//删除一个国际运输方式
function removeShippingService(obj){
	$(obj).parent().parent().parent().parent().parent().remove();
}
//选择国家
function selectLocation(obj){
	if(obj.value=='Worldwide'){
		$(obj).parent().find("#locations").hide(0);
	}else{
		$(obj).parent().find("#locations").show(0);
	}
}

<?php 
	if($shippingTemplate){
		foreach ($shippingTemplate as $template){
			$type = $template['shipping_type']=='1' ? 'domestic' : 'international';
			echo "addShippingService('$type','{$template['shipping_service']}','{$template['cost_type']}','{$template['additional_cost']}','{$template['ship_location']}');";
		}
	}
?>
</script>