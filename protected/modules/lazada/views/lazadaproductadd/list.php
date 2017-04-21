<style type="text/css">
.grid .gridTbody td div{height:auto;padding-top:2px;}
</style>
<?php
$row = 0;
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'lazadaproductadd-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array (
				array (
						'text' => Yii::t ( 'lazada', 'Upload Product' ),
						'url' => Yii::app()->createUrl('/lazada/lazadaproductadd/upload'),
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'selectedTodo',
								'rel' => 'lazadaproductadd-grid',
								'postType' => 'string',
								'callback' => 'navTabAjaxDone' 
						) 
				),
				array(
						'text'          => Yii::t('lazada', 'Batch delete messages'),
						'url'           => '/lazada/lazadaproductadd/delete',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to delete these records?'),
								'target'    => 'selectedTodo',
								'mask'		=>true,
								'rel'       => 'lazadaproductadd-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
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
                                                'htmlOptions' => array('style' => 'width:20px;'),
				),
				array(
						'name'	=>	'site_name',
						'value' => '$data->site_name',
						'type' 	=> 'raw',
                                                'htmlOptions' => array('style' => 'width:30px;'),
				),
				array(
						'name'	=>	'account_name',
						'value' => '$data->account_name',
						'type' 	=> 'raw',
                                                'htmlOptions' => array('style' => 'width:90px;'),
				),
                array(
						'name' => 'type',
						'value'=> 'LazadaProductAdd::getListingType($data->listing_type)',
                                                'htmlOptions' => array('style' => 'width:60px;'),
				),
                array(
                        'name'  => 'status',
                        'value' => 'VHelper::getRunningStatusLable($data->status, $data->status_desc)',
                        'type'  => 'raw',
                        'htmlOptions' => array('style' => 'text-align:center;width:60px'),
                ),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
                        'type' => 'raw',
						'htmlOptions' => array(
							'style' => 'width:60px',
						),
				),
				array(
						'name' => 'seller_sku',
						'value' => '$data->seller_sku',
						'type'  => 'raw',
                        'htmlOptions' => array('style' => 'width:170px;'),
				),
                array(
						'name'  => 'seller_sku',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'text-align:center;width:170px;'),
				),
    //             array(
				// 		'name' => 'parent_sku',
				// 		'value' => array($this, 'renderGridCell'),
				// 		'type'  => 'raw',
    //                     'htmlOptions' => array('style' => 'width:100px;'),
				// ),
                array(
						'name'  => 'status_text',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'text-align:center;width:100px;'),
				),
                                array(
						'name'  => 'upload_message',
						'value' => array($this, 'renderGridCell'),
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'text-align:center;width:100px;'),
				),
				
				array(
						'name' => 'title',
						'value'=> 'VHelper::getBoldShow($data->title)',
                                                'type'  => 'raw',
                                                'htmlOptions' => array('style' => 'width:150px;'),
				),
				array(
						'name' => 'categoryname',
						'value'=> '$data->category_name',
                                                'htmlOptions' => array('style' => 'width:100px;'),
				),
				array(
						'name' => 'price',
						'value'=> 'VHelper::getRedBoldShow($data->price)',
                                                'type'  => 'raw',
                                                'htmlOptions' => array('style' => 'text-align:right;width:50px'),
				),
                                
				array(
						'name' => 'upload_time',
						'value'=>'$data->upload_time',
                                                'htmlOptions' => array('style' => 'width:100px;'),
				),
//				array(
//						'name'  => 'upload_user_id',
//						'value' => 'MHelper::getUsername($data->upload_user_id)',
//                                                'htmlOptions' => array('style' => 'width:100px;'),
//				),
				array(
						'name'  => 'message',
						'value' => '$data->upload_result',
                                                'type'  => 'raw',
                                                'htmlOptions' => array('style' => 'width:100px;'),
				),
				array(
						'name'  => 'add_type',
						'value' => '$data->add_type',
						'type'  => 'raw',
						'htmlOptions' => array('style' => 'width:60px;'),
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}&nbsp;&nbsp;{view}',
						'buttons' => array(
                            'edit' => array(
                                'url'       => 'Yii::app()->createUrl("/lazada/lazadaproductadd/update", array("id" => $data->id))',
                                'label'     => Yii::t('lazada', 'Edit Lazada Product'),
                                'options'   => array(
                                            'target'    => 'navTab',
                                            'class'     =>'btnEdit',
                                            'rel' => ''
                                ),
                                'visible' => '$data->is_visible == 1'
                            ),
                            'view' => array(
                                'url'       => 'Yii::app()->createUrl("/lazada/lazadaproductadd/view", array("id" => $data->id))',
                                'label'     => Yii::t('lazada', 'View Lazada Product'),
                                'options'   => array(
                                            'target'    => 'navTab',
                                            'class'     =>'btnView',
                                            'rel' => ''
                                ),
                                'visible' => '$data->success_is_visible == 1'
                            ),
						),
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));
?>