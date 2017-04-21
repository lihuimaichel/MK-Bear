<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'aliexpressaccount-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'aliexpress/aliexpressaccount/lockaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'aliexpressaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'aliexpress/aliexpressaccount/unlockaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'aliexpressaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				// array(
				// 		'text'          => Yii::t('system', 'ShutDown'),
				// 		'url'           => 'aliexpress/aliexpressaccount/shutdownaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'delete',
				// 				'title'     => Yii::t('system', 'Really want to shutdown the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'aliexpressaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Open'),
				// 		'url'           => 'aliexpress/aliexpressaccount/openaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to open the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'aliexpressaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Activation Account'),
				// 		'url'           => 'aliexpress/aliexpressaccount/activation/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to activation the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'aliexpressaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
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
					'name' => 'is_overseas_warehouse',
					'value' => 'AliexpressAccount::getWarehouseLable($data->is_overseas_warehouse)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
    	    array(
    	        'name' => 'status',
    	        'value' => 'AliexpressAccount::getStatusLable($data->status)',
    	        'htmlOptions' => array('style' => 'width:150px;'),
    	    ),	    
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),
					'template' => '{updates}<br />{authorize}<br />{delimage}',
					'buttons' => array(
						'updates' => array(
								'url'       => 'Yii::app()->createUrl("/aliexpress/aliexpressaccount/update", array("id" => $data->id))',
								'label'     => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options'   => array(
										'target'    => 'dialog',
										'mask'		=>true,
										'rel' 		=> 'aliexpressaccount-grid',
										'width'     => '300',
										'height'    => '200',
								),
						),
						'authorize' => array(
								'url'       => 'Yii::app()->createUrl("aliexpress/aliexpressaccount/authorize/", array("id" => $data->id))',
								'label'     => Yii::t('system', '授权'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options'   => array('rel' 		=> 'aliexpressaccount-grid'),
						),
                        'delimage' => array(
                                                                'url'       => 'Yii::app()->createUrl("aliexpress/aliexpressdeleteimage/index/", array("account_id" => $data->id))',
								'label'     => Yii::t('system', '删除未被引用图片'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options'   => array(
                                                                    'title'     => Yii::t('aliexpress', '确定要删除该账号的未被引用图片吗？'),
                                                                    'target'    => 'ajaxTodo',
                                                                    'callback'  => 'navTabAjaxDone',
                                                                    'rel' 	=> 'aliexpressaccount-grid'
                                                                ),
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