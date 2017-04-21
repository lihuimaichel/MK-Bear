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
	<div class="pageContent"> 
	    <div class="pageFormContent"> 
	    	<div class="bg14 pdtb2 dot">
		         <strong><?php echo Yii::t('system', 'Choose Site')?></strong>           
		    </div>
	        <div class="pd5" style="height:auto;line-height:400px;">
	        	<div class="row">
	        		<?php echo CHtml::label("选择站点", 'choose_site_id', array());?>
	        		<?php echo CHtml::dropDownList('choose_site_id', '', EbaySite::getSiteList(), array('empty'=>Yii::t('system', 'Please Select')));?>
	        		<span></span>
	        	</div>
	        </div>
	    </div>
	    <div class="formBar">
	        <ul>              
	            <li>
	                <div class="buttonActive">
	                    <div class="buttonContent">                        
	                        <a onclick="nextStep()"><?php echo Yii::t('system', 'Save')?></a>   
	                        <a id="nextaddstep" style="display: none;width:680px;height:850px;" width="680" height="850" href="<?php echo Yii::app()->createUrl("ebay/ebayattributetemplate/add", array('site_id'=>'__SITE_ID__'));?>" rel="ebay_product_attribute_template_list" target="dialog">站点参数设置</a>                 
	                    </div>
	                </div>
	            </li>
	            <li>
	                <div class="button"><div class="buttonContent"><button type="button" class="close"><?php echo Yii::t('system', 'Cancel')?></button></div></div>
	            </li>
	        </ul>
	    </div>
	</div>


<script type="text/javascript">
	function nextStep(){
		var siteId = parseInt($("#choose_site_id").val());
		var href = $("#nextaddstep").attr("href");
		if(siteId>=0){
			href = href.replace('__SITE_ID__', siteId);
			$("#nextaddstep").attr("href", href).click();
		}else{
			alertMsg.error("选择站点ID");
			return;
		}
		
	}

	function callbackResize(){
		console.log('callbackResize');
	}
</script>