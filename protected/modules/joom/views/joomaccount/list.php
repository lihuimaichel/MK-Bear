<?php

Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'joomaccount-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'joom/joomaccount/lockaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'joomaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'joom/joomaccount/unlockaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'joomaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				// array(
				// 		'text'          => Yii::t('system', 'ShutDown'),
				// 		'url'           => 'joom/joomaccount/shutdownaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'delete',
				// 				'title'     => Yii::t('system', 'Really want to shutdown the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'joomaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Open'),
				// 		'url'           => 'joom/joomaccount/openaccount/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to open the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'joomaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Activation Account'),
				// 		'url'           => 'joom/joomaccount/activation/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to activation the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'joomaccount-grid',
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
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'is_lock',
					'value' => 'JoomAccount::getLockLable($data->is_lock)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'JoomAccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			// array(
			// 		'header' => Yii::t('system', 'Operation'),
			// 		'class' => 'CButtonColumn',
			// 		'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),
			// 		'template' => '{updates}',
			// 		'buttons' => array(
			// 			'updates' => array(
			// 					'url'       => 'Yii::app()->createUrl("/joom/joomaccount/update", array("id" => $data->id))',
			// 					'label'     => Yii::t('system', 'Edit'),
			// 					'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
			// 					'options'   => array(
			// 							'target'    => 'dialog',
			// 							'mask'		=>true,
			// 							'rel' 		=> 'joomaccount-grid',
			// 							'width'     => '300',
			// 							'height'    => '200',
			// 					),
			// 			)
			// 	),
			// ),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>