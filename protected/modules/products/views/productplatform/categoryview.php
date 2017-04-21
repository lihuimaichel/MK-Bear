<style type="text/css">
.grid .gridTbody td div{
	height:auto; 
	padding-top:2px;
}
.left h25 ml10 filterToggle{
	width:600px;
}
</style>

<script language="javascript">
	$(document).ready(function() {
		//重写提交的参数
	var actions =	$('#productPublish-grid form').attr("action"); 
	var urlArr = actions.split('?');
	var url =actions.substr(0,52);
	$('#productPublish-grid form').attr("action",url+'?'+urlArr[1]); 
		var status =[0,1,2,3,4,5,6,7];
		var  product_status = "<?php if( $_REQUEST['product_status']){echo implode(',',$_REQUEST['product_status']);}else{echo $_REQUEST['product_status_str'];} ?>";
		
		//  console.log(product_status);
		   if(product_status != ''){ 
		       $.each(status,function(k,v){
			          var checks = $("#productPublish-grid #product_status_"+v).val();
			          if(product_status.indexOf(checks) > -1 )
			          {
			        	  $("#productPublish-grid #product_status_"+v).attr('checked', 'true');
			          }
			
			       })
			     } 
		  // 	  $("#productPublish-grid #product_status_4").attr('checked', 'true');
		   	//  $("#productPublish-grid #product_status_6").attr('checked', 'true');
			   	<?php 	//  } ?>

//	       if(<?php echo $_REQUEST['ispublish'] ?>){
// 	    	   $("#productPublish-grid #online_status_0").attr('checked', 'true');
// 		    }
  
	});

</script>
<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$config = array(
    'id' => 'productPublish-grid',
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
        'value' => '$data->id',
        'htmlOptions' => array( 'style' => 'width:5px;'),
    ),
	array(
		'name' => 'id',
		'value' => '$row+1',
		'htmlOptions' => array( 'style' => 'width:40px;'),
	),

	array(
    		'name' => 'sku',
    		'value' => '$data->sku',
			'htmlOptions' => array( 'style' => 'width:100px;'),
		),
	
	array(
		'name' => 'product_title',
		'value' => '$data->product_title',
		'htmlOptions'=>array('width'=>'200px'),
	),

	array(
		'name' => 'category_id',
		'value' => 'UebModel::model ( "ProductClass")->getClassInfoById($data->category_id)',
		//'value' => '$data->category_id',
		'htmlOptions'=>array('width'=>'100px'),
	),
	array(
			'name' => 'product_status',
			'value' => 'VHelper::getProductStatusLabel($data->product_status)',
			'htmlOptions'=>array('width'=>'100px'),
	),
);
  

$this->widget('UGridView', $config);
?>


