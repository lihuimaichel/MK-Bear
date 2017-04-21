<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$this->widget("UGridView", array(
	'id'	=>	'ebay_product_add_list',
	'filter'	=>	$model,
	'dataProvider'	=>	$model->search(),
	'selectableRows'	=>	2,
	'columns'	=>	array(
					array(
						'class'=>'CCheckBoxColumn',
						'value'=>'$data->id',
						'selectableRows' => 2,
						'htmlOptions' => array(
								'style' => 'width:20px;',
						
						),
						'headerHtmlOptions' => array(
								'align' => 'center',
								'style' => 'width:20px;',
								'onclick'=>''
						),
						'checkBoxHtmlOptions' => array(
								'onchange' => '',
								'onpropertychange' => '',
								'oninput' => '',
						),
							
					),
					
					array(
							'name'=>'sku',
							'value' => '$data->sku',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			
					array(
							'name'=>'listing_duration',
							'value' => '$data->listing_duration',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'site_name',
							'value' => '$data->site_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'account_name',
							'value' => '$data->account_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:50px;',
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					
					array(
							'name'=>'category_title',
							'value' => '$data->category_title',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:260px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'sale_prices',
							'value' => '$data->sale_prices',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:160px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
			
					array(
							'name'=>'status_text',
							'value' => '$data->status_text',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					array(
							'name'=>'upload_count',
							'value' => '$data->upload_count',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'template' => '{edit}&nbsp;&nbsp;&nbsp;{update1}',
					'htmlOptions' => array(
							'style' => 'text-align:center;width:160px;',
					),
					'buttons' => array(
							'edit' => array(
									'url'       => 'Yii::app()->createUrl("/ebay/ebayproductadd/update", array("add_id" => $data->id))',
									'label'     => Yii::t('ebay', 'Edit Public Add Info'),
									'options'   => array(
											'target'    => 'navTab',
											'rel' 		=> 'ebay_product_add_list',
											'class'     =>	'btnEdit',
											'postType' => '',
											'callback' => '',
									),
									'visible'	=>	'($data->status == 1 || $data->status==3 || $data->status==5)'
							),
								
							'update1' => array(
									'url'       => 'Yii::app()->createUrl("/ebay/ebayproductadd/uploadproduct", array("add_id" => $data->id))',
									'label'     => Yii::t('aliexpress', 'Upload Now'),
									'options'   => array(
											'title'     => Yii::t('aliexpress', 'Are you sure to upload these'),
											'target'    => 'ajaxTodo',
											'rel'       => 'aliexpressproductadd-grid',
											'postType'  => 'string',
											'callback'  => 'navTabAjaxDone',
											'onclick'	=>	'',
											'style'		=>	'width:80px;height:28px;line-height:28px;'
									),
									'visible'	=>	'$data->visiupload'
							),
					),
			),
			
			array(
					'name'=>'listing_type',
					'value' => '$data->listing_type',
					'type'=>'raw',
					'htmlOptions' => array(
							'style' => 'width:60px;',
								
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
			
			),
			array(
					'name'=>'add_type',
					'value' => '$data->add_type_text',
					'type'=>'raw',
					'htmlOptions' => array(
							'style' => 'width:40px;',
			
					),
					'headerHtmlOptions' => array(
							'align' => 'center',
					),
						
			),
					//update_user_id,update_time,last_response_time
					array(
							'name'=>'update_user_id',
							'value' => 'MHelper::getUsername($data->update_user_id)',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'config_name',
							'value' => '$data->config_name',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
			
					array(
							'name'=>'create_time',
							'value' => '$data->create_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
			
					array(
							'name'=>'last_response_time',
							'value' => '$data->last_response_time',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
					
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
								
					),
					array(
							'name'=>'item_id',
							'value' => '$data->item_id',
							'type'=>'raw',
							'htmlOptions' => array(
									'style' => 'width:100px;',
										
							),
							'headerHtmlOptions' => array(
									'align' => 'center',
							),
					
					),
					
				),
	'toolBar'	=>	array(
		
			array(
					'text' => Yii::t('system', 'Batch delete messages'),
					'url' => Yii::app()->createUrl('ebay/ebayproductadd/batchdel'),
					'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to delete these records?' ),
								'target' => 'selectedTodo',
								'rel' => 'ebay_product_add_list',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
					) 
			),
			
			
			array(
					'text' => Yii::t('ebay', 'Batch Upload'),
					'url' => Yii::app()->createUrl('ebay/ebayproductadd/batchupload'),
					'htmlOptions' => array (
							'class' => 'add',
							'title' => Yii::t ( 'ebay', 'Really want to upload these records?' ),
							'target' => 'selectedTodo',
							'rel' => 'ebay_product_add_list',
							'postType' => 'string',
							'warn' => Yii::t ( 'system', 'Please Select' ),
							'callback' => 'navTabAjaxDone'
					)
			),
			//Updatedesc
			//160919
			/* array(
					'text' => Yii::t('ebay', '批量更新详情和图片'),
					'url' => Yii::app()->createUrl('ebay/ebayproductadd/updatedesc'),
					'htmlOptions' => array (
							'class' => 'add',
							'title' => Yii::t ( 'ebay', '确定要更新这些详情和图片吗' ),
							'target' => 'selectedTodo',
							'rel' => 'ebay_product_add_list',
							'postType' => 'string',
							'warn' => Yii::t ( 'system', 'Please Select' ),
							'callback' => 'navTabAjaxDone'
					)
			),
                       */  
	),
	'pager' => array(),
	'tableOptions' => array(
			'layoutH' => 150,
			'tableFormOptions' => true,
	),
));


?>
<script type="text/javascript">
function showErrorMsg(obj){
	$(obj).attr();
	console.log('showErrorMsgxxxx');
}
</script>