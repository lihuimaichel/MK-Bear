<style>
<!-- 
.grid .gridTbody td{
	padding:4px 3px;
}
-->
</style>
<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'amazonproductadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(
				array(
						'text' => Yii::t('amazon_product', 'Delete Data'),
						'url' => Yii::app()->createUrl('/amazon/amazonproductadd/selecteddelete'),
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('amazon_product', 'Really want to delete the data'),
								'target'    => 'selectedTodo',
								'rel'       => 'amazonproductadd-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				)							
		),	

	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
						'htmlOptions' => array('style' => 'width:20px;'),
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:35px;',
						),
				),
				array(
						'header' => Yii::t('amazon_product', 'Upload Operation'),
						'name' => 'sub_upload_status',
						'value'=> array($this, 'renderGridCell'),	//'$data->upload_start_time',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:135px;'),
				), 				
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
				 		'htmlOptions' => array(
				 				'style' => 'text-align:center;width:25px;',
				 		),
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/amazon/amazonproductadd/update", array("id" => $data->id))',
										'label'     => Yii::t('amazon_product', 'Edit Publish Info'),
										'options'   => array(
												'target' => 'navTab',
												'class'  =>'btnEdit',
												'rel'    => 'page351',
												'style'  =>'margin-left:2px;'
 										),
								),
						)
				),				
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:130px;'),
				),				
				array(
						'name' => 'account_name',
						'value' => '$data->account_name',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),	
/*	
				array(
						'name' => 'publish_type',
						'value'=> '$data->publish_type',
						'htmlOptions' => array('style' => 'text-align:center;width:60px;'),
				),
*/
				array(
						'name' => 'title',
						'value'=> 'VHelper::getBoldShow($data->title)',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name' => 'category_name',
						'value'=> '$data->category_name',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name' => 'sub_seller_sku',
						'value' => array($this, 'renderGridCell'),
				        'type'  => 'raw',				        
				        'htmlOptions' => array('style' => 'text-align:left;width:170px;','title' => array($this, 'renderGridCell')),
				),
				// array(
				// 		'name' => 'sub_product_id',
				// 		'value' => array($this, 'renderGridCell'),
				//         'type'  => 'raw',
				//         'htmlOptions' => array('style' => 'text-align:left;width:90px;'),
				// ),				
				array(
						'name' => 'product_price',
						'value' => array($this, 'renderGridCell'),
				        'type'  => 'raw',
				        'htmlOptions' => array('style' => 'text-align:center;width:50px;'),
				),
				// array(
				// 		'name' => 'product_quantity',
				// 		'value' => array($this, 'renderGridCell'), //'value'=> '$data->product_quantity',
				//         'type'  => 'raw',
				//         'htmlOptions' => array('style' => 'text-align:center;width:50px;'),
				// ),	

    		    array(
    		    	'header' => Yii::t('amazon_product', 'UPLOAD STATUS'),
    		        'name'  => 'sub_status_desc',
    		        'value' => array($this, 'renderGridCell'),	//'VHelper::getRunningStatusLable($data->status, $data->status_desc)',
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;width:180px;',
    		        ),
    		    ),   		    
				array(
						'header' => Yii::t('amazon_product', 'Publish Time'),
						'name' => 'sub_upload_time',
						'value'=> array($this, 'renderGridCell'),	//'$data->upload_start_time',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:110px;'),
				),

				array(
						'name'  => 'upload_user_id',
						'value' => 'MHelper::getUsername($data->create_user_id)',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:50px;',
						),						
				),
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>
<script>
	$(".reuploadstatus").live('click', function(event){
		var vid = $(this).attr('variation_id');
		alertMsg.correct('确认要执行此操作吗？');
		if (vid){
			var data = 'variation_id=' + vid;
			$.ajax({
				type: 'post',
				url: '<?php echo Yii::app()->createUrl('amazon/amazonproductadd/reuploadstatus');?>',
				data:data,
				dataType:'json',
				success:function(result){
					if(result.statusCode != '200'){
						alertMsg.error(result.message);
					}else{
						alertMsg.correct(result.message);
					}
				}					
			});
		}
		event.stopPropagation();
	});	
</script>