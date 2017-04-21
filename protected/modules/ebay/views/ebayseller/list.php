<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'ebayseller-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(
				array(
						'text'          => Yii::t('system', 'Add'),
						'url'           => '/ebay/ebayseller/createselleraccount/',
						'htmlOptions'   => array(
								'class'     => 'add',
								'target'    => 'dialog',
								'mask'		=>true,
								'rel'       => 'ebayseller-grid',
								'postType'  => '',
								'callback'  => '',
								'width'     => '600',
								'height'    => '400',
								'onclick'	=> false
						)
				),
				array(
						'text'          => Yii::t('system', 'ShutDown'),
						'url'           => 'ebay/ebayseller/shutdownaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to shutdown the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'ebayseller-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
								'onclick'	=> false
						)
				),
				array(
						'text'          => Yii::t('system', 'Open'),
						'url'           => 'ebay/ebayseller/openaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to open the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'ebayseller-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
								'onclick'	=> false
						)
				),
				array(
						'text'          => Yii::t('system', 'Delete'),
						'url'           => 'ebay/ebayseller/deleteaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to delete the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'ebayseller-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
								'onclick'	=> false
						)
				),
				array(
						'text'          => Yii::t('ebay', 'Update Seller Items'),
						'url'           => 'ebay/ebayseller/updateitem/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('ebay', 'Really Want To Update Selected Seller Items'),
								'target'    => 'selectedTodo',
								'rel'       => 'ebayseller-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
								'onclick'	=> false
						)
				),
		),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->user_name',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'id',
					'value' => '$row+1',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'user_name',
					'value' => '$data->user_name',
					'htmlOptions' => array('style' => 'width:30px;color:blue;'),
			),
			array(
					'name' => 'status',
					'value' => 'VHelper::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'user_id',
					'value' => 'MHelper::getUsername($data->user_id)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'total_item_num',
					'value' => '$data->total_item_num',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'all_item_nums',
					'value' => '$data->all_item_nums',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
// 			array(
// 					'header' => Yii::t('system', 'Operation'),
// 					'class' => 'CButtonColumn',
// 					'headerHtmlOptions' => array('width' => '60', 'align' => 'center'),
// 					'htmlOptions' => array('align' => 'center',),
// 					'template' => '{changbrand}',
// 					'buttons' => array(
// 							'changbrand' => array(
// 									'label' => Yii::t('system', 'Edit'),
// 									'url' => 'Yii::app()->createUrl("/lazada/lazadaaccount/updateaccount", array("id" => $data->id))',
// 									'title' => Yii::t('system', 'Edit'),
// 									'options' => array('target' => 'dialog','class'=>'btnEdit','mask'=>true,),
// 							),
// 					),
// 			)
	),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>