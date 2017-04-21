<?php

Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'shopeeaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'toolBar' => array(
				array(
						'text'          => Yii::t('system', 'Lock'),
						'url'           => 'shopee/shopeeaccount/lock/',
						'htmlOptions'   => array(
								'class'     => 'delete',
								'title'     => Yii::t('system', 'Really want to lock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'shopeeaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				array(
						'text'          => Yii::t('system', 'Unlock'),
						'url'           => 'shopee/shopeeaccount/unlock/',
						'htmlOptions'   => array(
								'class'     => 'edit',
								'title'     => Yii::t('system', 'Really want to unlock the account'),
								'target'    => 'selectedTodo',
								'rel'       => 'shopeeaccount-grid',
								'postType'  => 'string',
								'warn'      => Yii::t('system', 'Please Select'),
								'callback'  => 'navTabAjaxDone',
						)
				),
				// array(
				// 		'text'          => Yii::t('system', 'ShutDown'),
				// 		'url'           => 'shopee/shopeeaccount/close/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'delete',
				// 				'title'     => Yii::t('system', 'Really want to shutdown the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'shopeeaccount-grid',
				// 				'postType'  => 'string',
				// 				'warn'      => Yii::t('system', 'Please Select'),
				// 				'callback'  => 'navTabAjaxDone',
				// 		)
				// ),
				// array(
				// 		'text'          => Yii::t('system', 'Open'),
				// 		'url'           => 'shopee/shopeeaccount/open/',
				// 		'htmlOptions'   => array(
				// 				'class'     => 'edit',
				// 				'title'     => Yii::t('system', 'Really want to open the account'),
				// 				'target'    => 'selectedTodo',
				// 				'rel'       => 'shopeeaccount-grid',
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
            'name' => 'open_time',
            'value' => '$data->open_time',
            'htmlOptions' => array('style' => 'width:150px;'),
        ),
			array(
					'name' => 'is_lock',
					'value' => 'ShopeeAccount::getLockLable($data->is_lock)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),

			array(
					'name' => 'status',
					'value' => 'ShopeeAccount::getStatusLable($data->status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Department'),
                'name' => 'departmentName',
					'headerHtmlOptions' => array('width' => '40', 'align' => 'center',),

			),
		),
	'tableOptions' 	=> array(
			'layoutH' 	=> 90,
	),
	'pager' 		=> array(),
));
?>