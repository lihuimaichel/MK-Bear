<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'aliexpressproductadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(
			array (
					'text' => Yii::t ( 'system', '一键恢复上传中Listing' ),
					'url' => '/aliexpress/aliexpressproductadd/onekeyrestorelisting',
					'htmlOptions' => array (
							'class' => 'add',
							'target' => 'selectedTodo',
							'rel' => 'aliexpressproductadd-grid',
							'postType' => 'string',
							'callback' => '',
							'height' => '480',
							'width' => '650'
					)
			),
	    
			array (
					'text' => Yii::t ( 'system', '批量删除' ),
					'url' => '/aliexpress/aliexpressproductadd/batchdelete',
					'htmlOptions' => array (
							'class' => 'delete',
							'target' => 'selectedTodo',
							'selectedToDoCheck' => "batchDeleteCheck",
							'rel' => 'aliexpressproductadd-grid',
							'postType' => 'string',
							'callback' => "",
							'height' => '480',
							'width' => '650',
							'title' => '确定要删除？',
							'warn'  => '请选择信息!',
							'onclick' => ""
					)
			),
			
	),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:50px;',
						),
				),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),
				array(
						'name' => 'account_name',
						'value' => '$data->account_name',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),			
				array(
						'name' => 'publish_type',
						'value'=> '$data->publish_type',
						'htmlOptions' => array('style' => 'width:70px;'),
				),
				array(
						'name' => 'subject',
						'value'=> 'VHelper::getBoldShow($data->subject)',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:200px;'),
				),
				array(
						'name' => 'category_name',
						'value'=> '$data->category_name',
						'htmlOptions' => array('style' => 'width:250px;'),
				),
				array(
						'name' => 'product_price',
						'value'=> 'VHelper::getRedBoldShow($data->product_price)',
				        'type'  => 'raw',
				        'htmlOptions' => array(
				            'style' => 'text-align:right;width:70px;',    
                        ),
				),
				array(
						'name' => 'discount',
						'value'=> '$data->discount',
						'htmlOptions' => array('style' => 'width:70px;'),
				),
    		    array(
    		        'name'  => 'status',
    		        'value' => 'VHelper::getRunningStatusLable($data->status, $data->status_desc)',
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;width:70px;',
    		        ),
    		    ),
				array(
						'name' => 'upload_time',
						'value'=>'$data->upload_time',
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name'  => 'create_user_id',
						'value' => 'MHelper::getUsername($data->create_user_id)',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),						
				),
				array(
						'name'  => 'overseas_warehouse_name',
						'value' => 'AliexpressProductAdd::model()->getOverseasWarehouseName ($data->overseas_warehouse_id)',
				        'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:118px;',
						),
				),
				array(
						'name'  => 'upload_message',
						'value' => '$data->upload_message',
				        'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:168px;',
						),
				),
				 array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}&nbsp;&nbsp;&nbsp;{update1}',
				 		'htmlOptions' => array(
				 				'style' => 'text-align:center;width:99px;',
				 		),
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/aliexpress/aliexpressproductadd/update", array("id" => $data->id))',
										'label'     => Yii::t('aliexpress', 'Edit Publish Info'),
										'options'   => array(
												'target'    => 'navTab',
												'class'     =>'btnEdit',
												'rel' => 'page351'
										),
								),
								'update1' => array(
										'url'       => 'Yii::app()->createUrl("/aliexpress/aliexpressproductadd/uploadnow", array("add_id" => $data->id))',
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
			
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>

<script>
	
	function batchDeleteCheck($this){
		return true;
	}
</script>












