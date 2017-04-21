<style type="text/css">
.grid .gridTbody td div{
	height:auto;
	padding-top:2px;
}
</style>
<?php
$row = 0;
Yii::app ()->clientscript->scriptMap ['jquery.js'] = false;
$this->widget ( 'UGridView', array (
		'id' => 'ebayaccount-grid',
		'dataProvider' => $model->search(null),
		'filter' => $model,
		
		'toolBar' => array (
				array (
						'text' => Yii::t ( 'system', 'Lock Account' ),
						'url' => '/ebay/ebayaccount/lock',
						'htmlOptions' => array (
								'class' => 'delete',
								'title' => Yii::t ( 'system', 'Really want to lock these account?' ),
								'target' => 'selectedTodo',
								'rel' => 'ebayaccount-grid',
								'postType' => 'string',
								'warn' => Yii::t ( 'system', 'Please Select' ),
								'callback' => 'navTabAjaxDone' 
						) 
				),
				array (
						'text' => Yii::t ( 'system', 'Open Account' ),
						'url' => '/ebay/ebayaccount/open',
						'htmlOptions' => array (
								'class' => 'add',
								'target' => 'selectedTodo',
								'rel' => 'ebayaccount-grid',
								'postType' => 'string',
								'callback' => 'navTabAjaxDone' 
						) 
				),
				// array (
				// 		'text' => Yii::t ( 'system', 'Add' ),
				// 		'url' => '/ebay/ebayaccount/create',
				// 		'htmlOptions' => array (
				// 				'class' => 'add',
				// 				'target' => 'dialog',
				// 				'rel' => 'ebayaccount-grid',
				// 				'postType' => '',
				// 				'callback' => '',
				// 				'height' => '680',
				// 				'width' => '850' 
				// 		) 
				// ) 
		),
		'columns'=>array(
				array(
						'class' => 'CCheckBoxColumn',
						'selectableRows' =>2,
						'value'=> '$data->id',
						'htmlOptions' => array(
							'style' => 'width:20px;',
						)
				),
				array(
						'name'=> 'id',
						'value'=>'$data->id',
						'htmlOptions' => array(
							'style' => 'width:80px;',
						)
				),
				array(
						'name'=> 'short_name',
						'value'=>'$data->short_name',
						'htmlOptions' => array(
							'style' => 'width:80px;',
						)
				),
				array(
						'name' => 'user_name',
						'value' => '$data->user_name',
						'htmlOptions' => array(
							'style' => 'width:160px;',
						)
				),
				array(
						'name' => 'store_name',
						'value'=> '$data->store_name',
						'htmlOptions' => array(
							'style' => 'width:160px;',
						)
				),
				array(
						'name' => 'email',
						'value'=> '$data->email',
						'htmlOptions' => array(
							'style' => 'width:260px;',
						)
				),
				array(
						'name' => 'use_status',
						'value' =>'VHelper::getStatusLable($data->status)',
						'htmlOptions' => array(
							'style' => 'width:80px;',
						)
				),
				array(
						'name' => 'frozen_status',
						'value' => 'EbayAccount::getAccountLockStatus($data->is_lock)',
						'htmlOptions' => array(
							'style' => 'width:80px;',
						)
				),
				array(
						'name' => 'is_auto_upload',
						'value' => 'EbayAccount::getAccountAutoUploadStatus($data->is_auto_upload)',
						'htmlOptions' => array(
							'style' => 'width:100px;',
						)
				),
				array(
						'name' => 'group_id',
						'value' => '$data->group_id',
						'htmlOptions' => array(
							'style' => 'width:60px;',
						)
				),
				array(
						'header' => Yii::t('system', 'Operation'),
						'class' => 'CButtonColumn',
						'template' => '{edit}',
						'buttons' => array(
								'edit' => array(
										'url'       => 'Yii::app()->createUrl("/ebay/ebayaccount/update", array("id" => $data->id))',
										'label'     => Yii::t('system', 'Edit Paypal Account'),
										'options'   => array(
												'target'    => 'dialog',
												'class'     =>'btnEdit',
												'width'     => '850',
												'height'    => '680',
										),
								),
				
						),
						'htmlOptions' => array(
							'style' => 'width:100px;',
						)
				),
			
		),
		'tableOptions' => array (
				'layoutH' => 90 
		),
	    'pager' => array () 
));

?>