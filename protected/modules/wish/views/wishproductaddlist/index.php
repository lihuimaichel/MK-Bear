<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$options = array(
	'id' => 'wishproductadd-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
	'toolBar' => array(	
				array(
					'text' => Yii::t('wish_listing', 'Batch Delete'),
					'url' => Yii::app()->createUrl('/wish/wishproductaddlist/batchdel'),
					'htmlOptions' => array(
							'class' => 'delete',
							'title'     => Yii::t('wish_listing', 'Are you sure to delete these?! Note:Only delete not upload success'),
							'target'    => 'selectedTodo',
							'rel'       => 'wishproductadd-grid',
							'postType'  => 'string',
							'callback'  => 'navTabAjaxDone',
							'onclick'	=>	''
					)   
				),
			
			
			array(
					'text' => Yii::t('wish_listing', '批量上传'),
					'url' => Yii::app()->createUrl('/wish/wishproductadd/batchupload'),
					'htmlOptions' => array(
							'class' => 'add',
							'title'     => Yii::t('wish_listing', '确定要批量上传这些吗?注意不要一次选择过多，否则容易卡住'),
							'target'    => 'selectedTodo',
							'rel'       => 'wishproductadd-grid',
							'postType'  => 'string',
							'callback'  => 'navTabAjaxDone',
							'onclick'	=>	''
					)
			),
		),
	'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'	=> '$data->id',
						'disabled'=>'$data->upload_status==1',
						'htmlOptions'=>array(
										'attr-upload-status'=>'$data->upload_status'
									)
				),
				array(
						'name'=> 'id',
						'value'=>'$row+1',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:50px;',
						),
				),
				array(
						'name' => 'parent_sku',
						'value' => '$data->parent_sku',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:70px;'),
				),
				array(
						'name' => 'parent_sku',
						'value' => '$data->online_sku',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:100px;'),
				),
                               array(
						'name'  => 'online_sku',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:100px;',
						),
				),
				array(
						'name' => 'account_name',
						'value' => '$data->account_name',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:100px;'),
				),			
				
				array(
						'name' => 'name',
						'value'=> 'VHelper::getBoldShow($data->name)',
				        'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:350px;'),
				),
				array(
						'name'  => 'main_sku_upload_status',
						'value' => '$data->upload_status_text',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:100px;',
						),
				),
				array(
						'name'  => 'last_upload_msg',
						'value' => '$data->last_upload_msg',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'upload_times',
						'value' => '$data->upload_times',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'last_upload_time',
						'value' => '$data->last_upload_time',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'create_time',
						'value' => '$data->create_time',
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'create_user_id',
						'value' => 'MHelper::getUsername($data->create_user_id)',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				array(
						'name'  => 'add_type',
						'value' => '$data->add_type',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				),
				//=== 子sku
				/* array(
						'name'  => 'subsku',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:70px;',
						),
				), */
				
				array(
						'name'  => 'prop',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:left;width:100px;',
						),
				),
			
    		   array(
    		        'name'  => 'upload_status_text',
    		        'value' => array($this, 'renderGridCell'),
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;width:70px;',
    		        ),
    		    ),
				array(
						'name' => 'upload_times',
						'type'  => 'raw',
						'value'=>array($this, 'renderGridCell'),
						'htmlOptions' => array('style' => 'width:60px;'),
				),
				array(
						'name' => 'update_time',
						'type'  => 'raw',
						'value'=>array($this, 'renderGridCell'),
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name' => 'last_upload_time',
						'type'  => 'raw',
						'value'=>array($this, 'renderGridCell'),
						'htmlOptions' => array('style' => 'width:150px;'),
				),
				
				array(
						'name'  => 'last_upload_msg',
						'value' => array($this, 'renderGridCell'),
				        'type'  => 'raw',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:200px;',
						),
				),
				// === end 子sku
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}&nbsp;&nbsp;&nbsp;&nbsp;{update1}',
						'htmlOptions' => array(
								'style' => 'text-align:center;width:150px;',
						),
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/wish/wishproductadd/update", array("add_id" => $data->id))',
										'label'     => Yii::t('wish_listing', 'Edit Publish Info'),
										'options'   => array(
												'target'    => 'navTab',
												'class'     =>'btnEdit',
												'rel' => 'page366'
										),
								),
								'update1' => array(
										'url'       => 'Yii::app()->createUrl("/wish/wishproductadd/uploadproduct", array("add_id" => $data->id))',
										'label'     => Yii::t('wish_listing', 'Upload Now'),
										'options'   => array(
												'title'     => Yii::t('wish_listing', 'Are you sure to upload these'),
												'target'    => 'ajaxTodo',
												'rel'       => 'wishproductadd-grid',
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
);

$this->widget('UGridView', $options);

?>