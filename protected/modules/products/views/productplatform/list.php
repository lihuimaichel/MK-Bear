<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;

$config = array(
	'id' => 'productPlatform-grid',
    'dataProvider' => $model->search(),
    'filter' => $model,  
    'columns' => array(
        array(
            'class' => 'CCheckBoxColumn',
            'selectableRows' => 2,
            'value' => '$data->product_id',
            'htmlOptions' => array('style' => 'width:30px;'),
        ),
        array(
            'name' => 'id',
            'value' => '$row+1',
            'htmlOptions' => array( 'style' => 'width:40px;'),
        ),
    	array(
    		'name'   => 'sku',
    		'value'  => '$data->sku',
    	),
    	array(
    		'name'   => 'product_title',
    		'value'  => '$data->product_title',
    	),
    	array(
    		'name'   => 'product_status',
    		'value'  => 'Product::getProductStatusConfig($data->product_status)',
    	),
    	array(
    		'name'   => 'category_id',
    		'value'  => 'UebModel::model ( "ProductClass")->getClassInfoById($data->category_id)',
    	),
    	array(
    		'name' => 'platform_code',
    		'value' => 'UebModel::model("Platform")->getPlatformList(empty($data->platform_code) ? -1 : $data->platform_code)',
    	),
    	array(
    		'name'   => 'site',
    		'value'  => '$data->site',
    	),
    	array(
    		'name'   => 'seller_user_id',
    		'value'  => 'MHelper::getUsername($data->seller_user_id)',
    	),
    	array(
    		'name'   => 'account_id',
    		'value'  => 'UebModel::model("ProductToAccountRelation")->getPlatformAccountById($data->platform_code,$data->account_id)',
    	),
    ),
    'tableOptions' => array(
        'layoutH' => 135,
    	'style' => 'width:92%',
    ),
    'pager' => array(),
);
if ( Yii::app()->request->getParam('target') == 'dialog'  ) {
	$config['toolBar'] = array(
			array(
					'text'          => Yii::t('system', '批量待刊登( 备注：红色的代表已经是待刊登了)'),
					'url' => 'javascript:;',
					'htmlOptions' => array(
							'class' => 'edit readyPublish',
							// 				'id' => 'SelectProduct',
							'multLookup' => 'productPlatform-grid_c0[]',
							'rel' => '{target:"productPlatform", url: "products/productPlatform/selectproduct/target/dialog"}',
							'onclick'   => "BatchSelect();",
					)
			),

	);

}

$this->widget('UGridView', $config);
?>
<script>

function getAccount(obj){
	var str ='<option value="">所有</option>';
	var account_id  = '<?php  echo  $_REQUEST['account_id'];?>';
	if($(obj).val()){
		$.post('/orders/order/platformaccountbyid',{'platform':$(obj).val()},function(data){
			$.each(data,function(key,value){
				if(account_id == key){
					str +='<option selected="selected"  value="'+key+'">'+value+'</option>';
				}else{
					str +='<option value="'+key+'">'+value+'</option>';
				}
				
			});
			$(obj).parent().next().find("select").html(str);	
		},'json');
	}else{
		$(obj).parent().next().find("select").html(str);
	}

	
	var strSite ='<option value="">所有</option>';
	var site  = '<?php  echo  $_REQUEST['site'];?>';
	if($(obj).val()){
		$.post('/orders/order/platformsiteoffer',{'platform':$(obj).val()},function(data){
			if(data !=null){
				$.each(data,function(key,value){
					if(site == key){
						strSite +='<option selected="selected"  value="'+key+'">'+value+'</option>';
					}else{
						strSite +='<option value="'+key+'">'+value+'</option>';
					}
				});
			}
			$(obj).parent().next().next().find("select").html(strSite);	
		},'json');
	}else{
		$(obj).parent().next().next().find("select").html(strSite);
	}
}

$(document).ready(function() {
	//重写提交的参数
	var actions =	$('#productPlatform-grid form').attr("action"); 
	var urlArr = actions.split('?');
	var url =actions.substr(0,44);
	$('#productPlatform-grid form').attr("action",url+'?'+urlArr[1]);
	var status =[0,1,2,3,4,5,6,7];
	var product_status = "<?php if( !empty($_REQUEST['product_status'])){echo implode(',',$_REQUEST['product_status']);}else{echo $_REQUEST['product_status_str'];} ?>";
    if(product_status != ''){ 
       $.each(status,function(k,v){
	          var checks = $("#productPlatform-grid #product_status_"+v).val();
	          if(product_status.indexOf(checks) > -1 )
	          {
	        	  $("#productPlatform-grid #product_status_"+v).attr('checked', 'true');
	          }
	
	       })
	     } 
   <?php if(isset($_REQUEST['ispublish']) && $_REQUEST['ispublish'] == 1){?>
   			
    	   $("#productPlatform-grid #online_status_0").attr('checked', 'true');
	<?php }else if(isset($_REQUEST['ispublish']) && $_REQUEST['ispublish']==2){ ?>
	  		$("#productPlatform-grid #online_status_1").attr('checked', 'true');
	<?php }?>
    if($("#productPlatform-grid #online_status_1").is(":checked") == true && $("#productPlatform-grid #online_status_0").is(":checked") == false){

    	setAlreadySku();
    }else{
	    $("#productPlatform-grid .readyPublish").css("display",'none');
    }

	<?php 
	if(!empty($_REQUEST['ispublish'])){
	?>
	//$(".searchBar :checkbox", $.pdialog.getCurrent()).attr("disabled",true);
	//$(".searchBar select", $.pdialog.getCurrent()).attr("disabled",true);
	//$("#productPlatform-grid #reset").hide();
	<?php }?>

});

function BatchSelect(){
	var ids = "";
	var arrChk= $("#productPlatform-grid input[name='productPlatform-grid_c0[]']:checked",$.pdialog.getCurrent());
	var isMulti = $("#productPlatform-grid input[name='is_multi[]']:checked").val() ? 1 : 0;
	
	if(arrChk.length==0){
		alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
		return false;
	}
	//遍历得到每个checkbox的value值
    for (var i=0;i<arrChk.length;i++)
    {
		ids += arrChk[i].value+',';
    }
    var site = $('#productPlatform-grid #site').val();
    var seller = $('#productPlatform-grid #seller_user_id').val();
    var accountId = $('#productPlatform-grid #account_id').val();
    
    if(!seller){
		alertMsg.error('<?php echo Yii::t('system', '请选择销售员'); ?>');
		return false;
    }
    var platform_code = $('#productPlatform-grid #platform_code').val();
    if(!platform_code){
		alertMsg.error('<?php echo Yii::t('system', '请选择平台'); ?>');
		return false;
    }
    
    if($("#productPlatform-grid #online_status_1").is(":checked") == false || $("#productPlatform-grid #online_status_0").is(":checked") == true){
		alertMsg.error('<?php echo Yii::t('system', '请选择未刊登的sku'); ?>');
		return false;
    }
    if(platform_code == 'EB'|| platform_code == 'AMAZON'|| platform_code == 'LAZADA'){
        if(!site){
    		alertMsg.error('<?php echo Yii::t('system', '请选择站点'); ?>');
    		return false;
        }
        if(!accountId){
    		alertMsg.error('<?php echo Yii::t('system', '请选择账号'); ?>');
    		return false;
        }
    }

	if(platform_code == 'NF'){
		site ='nf';
	}else if(platform_code == 'KF'){
		site ='kf';
        if(!accountId){
    		alertMsg.error('<?php echo Yii::t('system', '请选择账号'); ?>');
    		return false;
        }
	}else if(platform_code == 'ALI'){
		site ='ali';
        if(!accountId){
    		alertMsg.error('<?php echo Yii::t('system', '请选择账号'); ?>');
    		return false;
        }
	}else if(platform_code == 'JM'){
        if(!accountId){
    		alertMsg.error('<?php echo Yii::t('system', '请选择账号'); ?>');
    		return false;
        }
	}
	
    $.post('/products/producttoaccountrelation/accounttoskuisok',{'ids':ids,'seller':seller,'platform_code':platform_code,'site':site,'accountId':accountId},function(data){
			if(data.status ==0){
				 var urls ='/products/producttoaccountrelation/setskupublishtime/ids/'+encodeURIComponent(ids)+'/seller/'+seller+'/platform_code/'+platform_code+'/site/'+site+'/accountId/'+accountId+"/is_multi/"+isMulti;
				 $.pdialog.open(urls, '1', '<?php echo Yii::t('common', '批量添加预计刊登时间');?>', {width: 400, height: 300,mask:true,fresh:true});
				
				//return false;
			}else{
				alertMsg.error(data.message);
				return false;
			}
			
        },'json');	

    $.pdialog.closeCurrent();

}
/**
 * 设置未刊登列表中已经存在待刊登的数据为加深颜色
 */
function setAlreadySku(){
	var ids = "";
	var arrChk= $("#productPlatform-grid input[name='productPlatform-grid_c0[]']",$.pdialog.getCurrent());
	if(arrChk.length>0){
		//遍历得到每个checkbox的value值
	    for (var i=0;i<arrChk.length;i++)
	    {
			ids += arrChk[i].value+',';
	    }
	    var site          = $('#productPlatform-grid #site').val();
	    var seller        = $('#productPlatform-grid #seller_user_id').val();
	    var accountId     = $('#productPlatform-grid #account_id').val();
	    var platform_code = $('#productPlatform-grid #platform_code').val();
	    $.post('/products/producttoaccountrelation/accounttoskuisokall',{'ids':ids,'seller':seller,'platform_code':platform_code,'site':site,'accountId':accountId},function(data){
			if(data.status ==1){
			    for (var j=0;j<arrChk.length;j++)
			    {
					if(data.idArr.indexOf(arrChk[j].value) !=-1){

						$("#productPlatform-grid #productPlatform-grid_c0_"+j).parent('td').parent('tr').css('color','red');
						$("#productPlatform-grid #productPlatform-grid_c0_"+j).attr("disabled",true);
					}
			    }
				//return false;
			}
			
	    },'json');
	}
}



</script>

