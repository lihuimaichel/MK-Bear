<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformebaydeveloperaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text'          => Yii::t('system', 'Add New Account'),
						'url'           => '/platformaccount/platformebaydeveloperaccount/add',
						'htmlOptions'   => array(
								'class'     => 'add',
								'target'    => 'dialog',
								'rel'       => 'platformebaydeveloperaccount-grid',
								'postType'  => '',
								'callback'  => '',
								'height'   => '400',
								'width'    => ''
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
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'appid',
					'value' => '$data->appid',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'devid',
					'value' => '$data->devid',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'certid',
					'value' => '$data->certid',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'ru_name',
					'value' => '$data->ru_name',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'max_nums',
					'value' => '$data->max_nums',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'status',
					'value' => 'PlatformEbayDeveloperAccount::getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'create_time',
					'value' => '$data->create_time',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'modify_time',
					'value' => '$data->modify_time',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '200', 'align' => 'center'),
					'template' => '{updates}',
					'buttons' => array(
						'updates' => array(
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformebaydeveloperaccount/edit", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformebaydeveloperaccount-grid',
									'width'     => '',
									'height'    => '350',
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