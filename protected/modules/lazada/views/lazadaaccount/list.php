<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'productstockprice-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
// 				array(
// 						'text'          => Yii::t('system', 'Add'),
// 						'url'           => '/products/productstockprice/createstockprice/',
// 						'htmlOptions'   => array(
// 								'class'     => 'add',
// 								'target'    => 'dialog',
// 								'mask'		=>true,
// 								'rel'       => 'productstockprice-grid',
// 								'postType'  => '',
// 								'callback'  => '',
// 								'width'     => '600',
// 								'height'    => '400',
// 						)
// 				),
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'lazada/lazadaaccount/lockaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'productstockprice-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'lazada/lazadaaccount/unlockaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'productstockprice-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				// array(
				// 		'text'          => Yii::t('system', 'ShutDown'),
				// 		'url'           => 'lazada/lazadaaccount/shutdownaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'delete',
				// 				'title'     => Yii::t('system', 'Really want to shutdown the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'productstockprice-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Open'),
				// 		'url'           => 'lazada/lazadaaccount/openaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to open the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'productstockprice-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
// 				array(
// 						'text'          => Yii::t('system', '取消二级审核'),
// 						'url'           => 'products/productstockprice/cancellvtwo/',
// 						'htmlOptions'   => array(
// 								'class'     => 'delete',
// 								'title'     => Yii::t('system', '确定要审核吗?'),
// 								'target'    => 'selectedTodo',
// 								'rel'       => 'productstockprice-grid',
// 								'postType'  => 'string',
// 								'warn'      => Yii::t('system', 'Please Select'),
// 								'callback'  => 'navTabAjaxDone',
// 						)
// 				),
				array(
						'text'          => '关闭自动调价',
						'url'           => 'lazada/lazadaaccount/closechangeprice',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => '是否关闭自动调价',
								'target'    => 'selectedTodo',
								'rel'       => 'productstockprice-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => '开启自动调价',
						'url'           => 'lazada/lazadaaccount/openchangeprice',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => '是否开启自动调价',
								'target'    => 'selectedTodo',
								'rel'       => 'productstockprice-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
		),
	'columns' => array(
			array(
					'class' => 'CCheckBoxColumn',
					'selectableRows' =>2,
					'value'=> '$data->id',
					'htmlOptions' => array('style' => 'width:5px;'),
			),
			array(
					'name' => 'id',
					'value' => '$row+1',
					'htmlOptions' => array('style' => 'width:30px;'),
			),
/* 			array(
					'name' => 'num',
					'value' => '$data->id',
					'htmlOptions' => array('style' => 'width:30px;color:blue;'),
			), */
			array(
					'name' => 'site_id',
					'value' => '$data->site_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),			
			array(
					'name' => 'seller_name',
					'value' => '$data->seller_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			
			array(
					'name' => 'is_lock',
					'value' => 'LazadaAccount::getLockLable($data->is_lock)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'LazadaAccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'is_change_price',
					'value' => 'LazadaAccount::getChangePriceStatus($data->is_change_price)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			// array(
			// 		'header' => Yii::t('system', 'Operation'),
			// 		'class' => 'CButtonColumn',
			// 		'headerHtmlOptions' => array('width' => '60', 'align' => 'center'),
			// 		'htmlOptions' => array('align' => 'center',),
			// 		'template' => '{changbrand}',
			// 		'buttons' => array(
			// 				'changbrand' => array(
			// 						'label' => Yii::t('system', 'Edit'),
			// 						'url' => 'Yii::app()->createUrl("/lazada/lazadaaccount/updateaccount", array("id" => $data->id))',
			// 						'title' => Yii::t('system', 'Edit'),
			// 						'options' => array('target' => 'dialog','class'=>'btnEdit','mask'=>true,),
			// 				),
			// 		),
			// )
	),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>