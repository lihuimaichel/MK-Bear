<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'jdaccount-grid',
	'dataProvider' => $model->search(null),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'jd/jdaccount/lockaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'jdaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'jd/jdaccount/unlockaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'jdaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'ShutDown'),
						'url'           => 'jd/jdaccount/shutdownaccount/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to shutdown the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'jdaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Open'),
						'url'           => 'jd/jdaccount/openaccount/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to open the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'jdaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Activation Account'),
						'url'           => 'jd/jdaccount/getaccesstoken/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to activation the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'jdaccount-grid',
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
					'htmlOptions' => array('style' => 'width:30px;'),
			),
			array(
					'name' => 'short_name',
					'value' => '$data->short_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'is_locked',
					'value' => 'jdaccount::getLockLable($data->is_locked)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'jdaccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),
					'template' => '{updates}',
					'buttons' => array(
						'updates' => array(
								'url'       => 'Yii::app()->createUrl("/jd/jdaccount/update", array("id" => $data->id))',
								'label'     => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options'   => array(
										'target'    => 'dialog',
										'mask'		=>true,
										'rel' 		=> 'jdaccount-grid',
										'width'     => '480',
										'height'    => '360',
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