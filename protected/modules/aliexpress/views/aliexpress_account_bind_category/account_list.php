<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$settingParams = '
                    array(
                        array(
                            "url" => "/aliexpress/aliexpress_account_bind_category/index",
                            "title" => "绑定分类设置",
                            "params" => array(
                                "account_id" => $data->id
                            ),
                            "style" => "h",
                        ),
                        array(
                            "url" => "javascript:void(0); displayCategoryBindDetail($data->id);",
                            "title" => "绑定详情",
                            "params" => array(
                                
                            ),
                            "style" => "w",
                            "target" => " ",
                        )    
                    )
                 ';
$row = 0;
$this->widget('UGridView', array(
	'id' => 'aliexpressaccount-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(					
		),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'short_name',
					'value' => '$data->short_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'is_lock',
					'value' => 'AliexpressAccount::getLockLable($data->is_lock)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'AliexpressAccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
    	    array(
        	        'name' => 'first_category',
        	        'value' => 'AliexpressAccountBindCategory::model()->getBindCategoryList($data->id,1,"account_list")',
        	        'htmlOptions' => array('style' => 'width:288px;'),
    	    ),
	       /*
    	    array(
        	        'name' => 'second_category',
        	        'value' => 'AliexpressAccountBindCategory::model()->getBindCategoryList($data->id,2))',
        	        'htmlOptions' => array('style' => 'width:150px;'),
    	    ),
    	    array(
        	        'name' => 'third_category',
        	        'value' => 'AliexpressAccountBindCategory::model()->getBindCategoryList($data->id,3)',
        	        'htmlOptions' => array('style' => 'width:150px;'),
    	    ),	
    	    
    	    */ 
	    
    	    array(
        	        'name' => 'setting',
        	        'value' => 'AliexpressAccountBindCategory::model()->mergeUrl('.$settingParams.')',
        	        'htmlOptions' => array('style' => 'width:150px;'),
    	    ),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>
<script>
	function displayCategoryBindDetail(accountId){
		$.ajax({
			type: 'post',
			url: '/aliexpress/aliexpress_account_bind_category/get_all_bind_category',
			data:{
				accountID:accountId
			},
			success:function(result){
				//alert(result);
				//console.log(result);
				alertMsg.confirm(result);
			},			
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				 //alert(XMLHttpRequest.status);
				console.log(XMLHttpRequest.status);
				 if (XMLHttpRequest.status == 500){
					 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
				 } else if (XMLHttpRequest.status == 404){
					 alertMsg.error('请求的页面不存在，404错误！');
				 } else if (XMLHttpRequest.status == 200){
					 $('#errorBox').html(XMLHttpRequest.responseText).fadeIn(666);
				 }
			},		
			dataType:'html'
		});	
	}
</script>
<div style="position:absolute; top:18%; left:8%; width:68%; height:58%; background:#f6f6f6; z-index:666666; display:none; padding:28px; font-size:18px; line-height:28px;" 
     id="errorBox" 
     onClick="$(this).html(''); $(this).fadeOut(666);"
><div>















