<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
		'id' => 'producttoaccountrelation-grid',
		'dataProvider' => $model->search(),
		'filter' => $model,
		'tableOptions' => array(
				'layoutH' => 90,
		),
		'pager' => array(),
);
$config['columns'] = array();
$config['columns'] = array(
		array(
				'class' => 'CCheckBoxColumn',
				'selectableRows' => 2,
				'value' =>'$data->id."_".$data->platform_code.VHelper::showTextHidden("platform_code_one",$data->platform_code,"",array("id"=>"platform_code_one"))',
				'htmlOptions' => array( 'style' => 'width:10px;'),
		
		),
		array(
				'name'=> 'id',
				'value'=>'$row+1',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:35px;'),
		),
		array(
				'name'=> 'sku',
				'type' => 'raw',
				//'value' => 'CHtml::link($data->sku,"/products/product/view/do/view/sku/".$data->sku,
    			//	array("title"=>$data->sku,"style"=>"color:blue","target"=>"dialog","width"=>1100,"mask"=>true,"height"=>600))',
				'value'	=>	'$data->sku',
				'htmlOptions' => array( 'style' => 'width:70px;'),
		),
		array(
				'name'=> 'product_status',
				'value'=>'VHelper::getProductStatusLabel($data->product_status)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'title',
				'value'=>'$data->title',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:200px;'),
		),
		array(
				'name'=> 'seller_user_id',
				'value'=>'MHelper::getUsername($data->seller_user_id)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'platform_code',
				'value'=>'UebModel::model("Platform")->getPlatformList($data->platform_code)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'site',
				'value'=>'$data->site',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'account_id',
				'value'=>'UebModel::model("ProductToAccountRelation")->getPlatformAccountById($data->platform_code,$data->account_id)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'is_to_image',
				'value'=>'UebModel::model("ProductToAccountRelation")->getImageStatus($data->is_to_image)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'ready_publish_time',
				'value'=>'$data->ready_publish_time',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'online_time',
				'value'=>'$data->online_time',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		
		array(
				'name'=> 'online_status',
				'value'=>'UebModel::model("ProductToAccountRelation")->getOnlineStatus($data->online_status)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		
		array(
				'name'=> 'ismulti',
				'value'=>'$data->ismulti',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		
		);
if ( Yii::app()->request->getParam('target') == 'dialog' ) {
// 	$config['toolBar'] = array(
// 			array(
// 					'text'          => Yii::t('system', 'Please Select'),
// 					'url' => 'javascript:;',
// 					'htmlOptions' => array(
// 							'class' => 'edit',
// 							// 				'id' => 'SelectProduct',
// 							'multLookup' => 'producttosellerrelation-grid_c0[]',
// 							'rel' => '{target:"producttosellerrelationList", url: "products/producttosellerrelation/selectproduct/target/dialog/key/'.$key.'"}',
// 							'onclick'   => "BatchSelect($key,'".$type."');",
// 					)
// 			),
// 	);

}else{

	$config['toolBar'] = array(
			array(
					'text'          => Yii::t('products', '添加账号分配'),
					'url'           => '/products/producttoaccountrelation/create',
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'producttoaccountrelation-grid',
							'postType'  => 'string',
							'mask'		=>true,
							'callback'  => '',
							'width' => '800',
							'height' => '400'
					)
			),
			array(
					'text' => Yii::t('products', '批量导入账号分配'),
					'url' => '/products/producttoaccountrelation/import',
					'htmlOptions' => array(
							'class' 	=> 'icon',
							'target' 	=> 'dialog',
							'mask'  	=> true,
							'width' 	=> '475',
							'height' 	=> '305',
							'onclick'   => false
					)
			),
			
// 			array(
// 					'text' => Yii::t('logistics', 'Batch delete the shipping service'),
// 					'url' => 'javascript:;',
// 					'htmlOptions' => array(
// 							'class' => 'delete',
// 							'rel' => 'producttoaccountrelation-grid',
// 							'onclick'   => 'getProducttoaccountrelationBySelect();',
// 					)
// 			),
			array(
					'text'          => Yii::t('system', 'Batch delete messages'),
					'url'           => '/products/producttoaccountrelation/removeskutoseller',
					'htmlOptions'   => array(
							'class'     => 'delete',
							'title'     => Yii::t('system', 'Really want to delete these records?'),
							'target'    => 'selectedTodo',
							'mask'		=>true,
							'rel'       => 'producttoaccountrelation-grid',
							'postType'  => 'string',
							'warn'      => Yii::t('system', 'Please Select'),
							'callback'  => 'navTabAjaxDone',
					)
			),
		);
	$config['columns'][] = array(
    				'header' => Yii::t('system', 'Operation'),
    				'class' => 'CButtonColumn',
    				'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),
    				'template' => '{updates}',
    				'buttons' => array(
    						'updates' => array(
    								'url'       => 'Yii::app()->createUrl("/products/producttoaccountrelation/update", array("id" => $data->id,"platform_code"=>$data->platform_code))',
    								'label'     => Yii::t('products', '编辑'),
    								'options'   => array(
    										'target'    => 'dialog',
    										'mask'		=>true,
    										//'class'     =>'edit',
    										'rel' 		=> 'producttosellerrelation-grid',
    										'width'     => '450',
    										'height'    => '400',
    								),
    						),


    				),
			);
}
$this->widget('UGridView', $config);



?>

<script>
$(document).ready(function() {
	var status =[0,1,2,3,4,5,6,7];
	var product_status = "<?php 
		if( isset($_REQUEST['product_status'])){
			if(is_array($_REQUEST['product_status'])){
					echo implode(',',$_REQUEST['product_status']);
			}else{
				echo $_REQUEST['product_status'];
			}
		}else{
			echo isset($_REQUEST['product_status_str']) ? $_REQUEST['product_status_str'] : '';
		}
		?>";

    if(product_status != ''){ 
       $.each(status,function(k,v){
	          var checks = $("#producttoaccountrelation-grid #product_status_"+v).val();
	          if(product_status.indexOf(checks) > -1 )
	          {
	        	  $("#producttoaccountrelation-grid #product_status_"+v).attr('checked', 'true');
	          }
	
	   })
	}

	<?php 
	if(!empty($_REQUEST['ispublish'])){
	?>
	//$(".searchBar :checkbox", $.pdialog.getCurrent()).attr("disabled",true);
	//$(".searchBar select", $.pdialog.getCurrent()).attr("disabled",true);
	//$("#producttoaccountrelation-grid #reset").hide();

	<?php }?>

});

$(document).ready(function(){ 
	$("#producttoaccountrelation-grid #platform_code option[value='']").remove();//删除索引值为0的Option
	 $("#producttoaccountrelation-grid #schemeTypeForm").hide();
	}); 
	
function getAccount(obj){
	var str ='<option value="">所有</option>';
	var account_id  = '<?php  echo  isset($_REQUEST['account_id']) ? $_REQUEST['account_id'] : 0;?>';
	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformaccountbyidnew',{'platform':$(obj).val()},function(data){
			$.each(data,function(key,value){
				key = key.substring(1);
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
	var site  = '<?php  echo  isset($_REQUEST['site']) ? $_REQUEST['site'] : '';?>';
	if($(obj).val()){
		$.post('/products/producttoaccountrelation/platformsiteoffer',{'platform':$(obj).val()},function(data){
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

function getProducttoaccountrelationBySelect(){
		var platform_code = $("#producttoaccountrelation-grid #platform_code_one").val();
		var ids = "";
		var arrChk= $("#producttoaccountrelation-grid input[name='producttoaccountrelation-grid_c0[]']:checked");
		if(arrChk.length==0){
			alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
			return false;
		}
		//遍历得到每个checkbox的value值
	    for (var i=0;i<arrChk.length;i++)
	    {
	    	ids += arrChk[i].value+',';

	    }
	    if(window.confirm('确认要删除已选择的SKU刊登账号站点信息？')){
	    	//  var urls ='/products/producttoaccountrelation/removeskutoseller/ids/'+encodeURIComponent(ids)+'/platform_code/'+platform_code;
	  	   // $.pdialog.open(urls, '1', '<?php echo Yii::t('products','批量删除');?>', {width: 400, height: 150,mask:true,fresh:true});
	  	
	        $.post('/products/producttoaccountrelation/removeskutoseller',{'platform_code':platform_code,'ids':ids},function(data){
	        	alertMsg.error(data.message);
	        	$("#producttoaccountrelation-grid").loadUrl('/products/producttoaccountrelation/list',data, '');
            },'json');	
	    }
	   // $.pdialog.closeCurrent();
}

function getSellerByEmp(obj){
	var strEmp ='<option value="">所有</option>';
	if($(obj).val()){
		$.post('/users/users/deptempuser',{'dept':$(obj).val()},function(data){
			$.each(data,function(key,value){
				strEmp +='<option value="'+key+'">'+value+'</option>';
			});
			$(obj).parent().next().find("select").html(strEmp);	
		},'json');
	}else{
		$(obj).parent().next().find("select").html(strEmp);
	}
}
</script>