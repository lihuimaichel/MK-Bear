<style type="text/css">
.grid .gridTbody td div{height:auto;padding-top:2px;}
</style>
<?php
$row = 0;
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'jdproductadd-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		'toolBar' => array (
/* 				array (
						'text' => Yii::t ( 'lazada', 'Upload Product' ),
						'url' => Yii::app()->createUrl('/lazada/lazadaproductadd/upload'),
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'selectedTodo',
								'rel' => 'lazadaproductadd-grid',
								'postType' => 'string',
								'callback' => 'navTabAjaxDone' 
						) 
				), */
				array(
						'text'          => Yii::t('jd', 'Batch delete messages'),
						'url'           => '/jd/jdproductadd/delete',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to delete these records?'),
								'target'    => 'selectedTodo',
								'mask'		=>true,
								'rel'       => 'jdproductadd-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
								'onclick' 	=> '',
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
				),
				array(
						'name' => 'sku',
						'value' => '$data->sku',
				        'type'  => 'raw',
				),
				array(
						'name' => 'publish_type',
						'value'=> 'JdProductAdd::getListingType($data->publish_type)',
				),
				array(
						'name' => 'title',
						'value'=> 'VHelper::getBoldShow($data->title)',
				        'type'  => 'raw',
				),
				array(
						'name' => 'categoryname',
						'value'=> '$data->category_name',
				),
				array(
						'name' => 'price',
						'value'=> 'VHelper::getRedBoldShow($data->price)',
				        'type'  => 'raw',
				        'htmlOptions' => array(
				            'style' => 'text-align:right;',    
                        ),
				),
    		    array(
    		        'name'  => 'status',
    		        'value' => 'VHelper::getRunningStatusLable($data->status, $data->status_desc)',
    		        'type'  => 'raw',
    		        'htmlOptions' => array(
    		            'style' => 'text-align:center;',
    		        ),
    		    ),
				array(
						'name' => 'upload_time',
						'value'=>'$data->upload_time',
				),
				array(
						'name'  => 'upload_user_id',
						'value' => 'MHelper::getUsername($data->upload_user_id)',
				),
				array(
						'name'  => 'message',
						'value' => '$data->upload_result',
				        'type'  => 'raw',
				),
/* 				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/lazada/lazadaproductadd/update", array("id" => $data->id))',
										'label'     => Yii::t('lazada', 'Edit Lazada Product'),
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '500',
												'height'    => '300',
										),
								),
				
						),
				), */
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));
?>