<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
 	'id' => 'producttosellerrelation-grid', 
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
				'value' =>'$data->id',
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
				'value'=>'$data->sku',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:70px;'),
		),
		array(
				'name'=> 'MarketersManager_emp_dept',
				'value'=>'$data->MarketersManager_emp_dept',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'seller_id',
				'value'=>'MHelper::getUsername($data->seller_id)',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'category_id',
				'value'=>'$data->category_id',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'online_one_id',
				'value'=>'$data->online_one_id',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		array(
				'name'=> 'product_status',
				'value'=>'$data->product_status',
				'type' =>'raw',
				'htmlOptions' => array( 'style' => 'width:100px;'),
		),
		);
if ( Yii::app()->request->getParam('target') == 'dialog' ) {
$config['toolBar'] = array(
			array(
					'text'          => Yii::t('system', 'Please Select'),
					'url' => 'javascript:;',
					'htmlOptions' => array(
							'class' => 'edit',
							// 				'id' => 'SelectProduct',
							'multLookup' => 'producttosellerrelation-grid_c0[]',
							'rel' => '{target:"producttosellerrelationList", url: "products/producttosellerrelation/selectproduct/target/dialog/key/'.$key.'"}',
							'onclick'   => "BatchSelect($key,'".$type."');",
					)
			),
	);

}else{
	
	$config['toolBar'] = array(
			array(
					'text'          => Yii::t('products', '添加销售人员分配'),
					'url'           => '/products/producttosellerrelation/create',
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'producttosellerrelation-grid',
							'postType'  => 'string',
							'mask'		=>true,
							'callback'  => '',
							'width' => '600',
							'height' => '400'
					)
			),
			array(
					'text' => Yii::t('products', '批量导入销售人员分配'),
					'url' => '/products/producttosellerrelation/import',
					'htmlOptions' => array(
							'class' 	=> 'icon',
							'target' 	=> 'dialog',
							'mask'  	=> true,
							'width' 	=> '475',
							'height' 	=> '305',
							'onclick'   => false
					)
			),
			array(
					'text' => Yii::t('logistics', 'Batch delete the shipping service'),
					'url' => '/products/producttosellerrelation/removeskutoseller',
					'htmlOptions' => array(
							'class' => 'delete',
							'title' => Yii::t('system', '您真的要解除SKU与销售人员的关系吗？'),
							'target' => 'selectedTodo',
							'rel' => 'producttosellerrelation-grid',
							'postType' => 'string',
							'callback' => 'navTabAjaxDone',
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
							'url'       => 'Yii::app()->createUrl("/products/producttosellerrelation/update", array("id" => $data->id))',
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
$(document).ready(function(){ 
<?php 
	if ( Yii::app()->request->getParam('target') == 'dialog' ) {
?>
var sellerId = $('#producttoaccountForm #ProductToAccountRelation_seller_user_id').val();
		if(!sellerId){
			
			alertMsg.error('请先选择销售员');
 			$("#producttosellerrelation-grid").parents('.dialog').find(".close").trigger('click');

		}
<?php }?>
}); 

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
function getClassOne(obj){
	var str ='<option value="">所有</option>';
	if($(obj).val()){
		$.post('/products/productclass/getproductcategoryone',{'category_id':$(obj).val()},function(data){
			$.each(data,function(key,value){

				str +='<option value="'+key+'">'+value+'</option>';
				
			});
			$(obj).parent().next().find("select").html(str);	
		},'json');
	}else{
		$(obj).parent().next().find("select").html(str);
	}
}

function BatchSelect(key,type){
	var parentObj = $('.dataintable_inquire').parent();
	var arrSku= $(".skuInquire");

	var isRepeat = false;
	var productIdArr = [];
	arrSku.each(function(){
		var curProductId = $('#product_id_'+this.id.substr(4)).val();
		if(curProductId != ''){
			productIdArr.push(curProductId);
		}
	});
	
	var ids = "";
	var arrChk= $("input[name='producttosellerrelation-grid_c0[]']:checked",$.pdialog.getCurrent());
	if(arrChk.length==0){
		alertMsg.error('<?php echo Yii::t('system', 'Please Select'); ?>');
		return false;
	}
	//遍历得到每个checkbox的value值
    for (var i=0;i<arrChk.length;i++)
    {
	    if($.inArray(arrChk[i].value , productIdArr) != -1){
	    	isRepeat = true;
		}else{
    		ids += arrChk[i].value+',';
		}
    }
    var seller = $('#ProductToAccountRelation_seller_user_id').val();
	//debugger;
	//alert(seller);
        $.post('/products/producttosellerrelation/sellertoskuisok',{'ids':ids,'seller':seller},function(data){
				if(data.statues ==1){
				    var urls ='/products/producttosellerrelation/selectproduct/ids/'+encodeURIComponent(ids)+'/key/'+key+'/type/'+type;
				    $.ajax({
				        type: "get",
				        url: urls,
				        dataType:'html',
				        success: function(data) {
				            if(data!=''){
				            	$(parentObj).find('tbody').append(data);
				            }
				        }
				    });
				}else{
					alertMsg.error('<?php echo Yii::t('system', '请选择属于选定销售员的SKU'); ?>');
					return false;
				}
				
            },'json');	

    $.pdialog.closeCurrent();

}
</script>