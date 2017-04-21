<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformaliexpressaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
		'toolBar' => array(	
				array(
						'text'          => Yii::t('system', 'Add New Account'),
						'url'           => '/platformaccount/platformaliexpressaccount/add',
						'htmlOptions'   => array(
								'class'     => 'add',
								'target'    => 'dialog',
								'rel'       => 'platformaliexpressaccount-grid',
								'postType'  => '',
								'callback'  => '',
								'height'   => '400',
								'width'    => ''
						)
				),
				array(
					'text' => Yii::t('system', 'Detection Token'),
					'url'  => '/platformaccount/platformaliexpressaccount/verificationtoken',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'platformaliexpressaccount-grid',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
						'width'    => ''
					)
				),
				array(
					'text' => Yii::t('system', 'Refresh Token'),
					'url'  => '/platformaccount/platformaliexpressaccount/updatetoken',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'platformaliexpressaccount-grid',
						'title'	   => '确定是否刷新token',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
						'width'    => ''
					)
				),
				array(
					'text' => '刷新refresh',
					'url'  => '/platformaccount/platformaliexpressaccount/updaterefreshtoken',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'platformaliexpressaccount-grid',
						'title'	   => '确定是否刷新refreshtoken',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
						'width'    => ''
					)
				),
				array(
					'text' => '同步到速卖通账号',
					'url'  => '/platformaccount/platformaliexpressaccount/tooms',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'platformaliexpressaccount-grid',
						'title'	   => '确定同步到速卖通账号吗?',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
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
					'name' => 'short_name',
					'value' => '$data->short_name',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'status',
					'value' => 'PlatformAliexpressAccount::getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'token_status',
					'value' => 'PlatformAliexpressAccount::getStatus($data->token_status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'refresh_token_status',
					'value' => 'PlatformAliexpressAccount::getStatus($data->refresh_token_status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'token_invalid_time',
					'value' => '$data->token_invalid_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'update_time',
					'value' => '$data->update_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'to_oms_status',
					'value' => 'PlatformAliexpressAccount::getOmsStatus($data->to_oms_status)',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'to_oms_time',
					'value' => '$data->to_oms_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'department_id',
					'value' => '$data->department_id',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'header' => Yii::t('system', 'Operation'),
					'class' => 'CButtonColumn',
					'headerHtmlOptions' => array('width' => '200', 'align' => 'center'),
					'template' => '{updates}&nbsp;&nbsp;&nbsp;&nbsp;{reautoorization}&nbsp;&nbsp;&nbsp;&nbsp;{setapi}',
					'buttons' => array(
						'updates' => array(
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformaliexpressaccount/edit", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformaliexpressaccount-grid',
									'width'     => '',
									'height'    => '',
								),
						),
						'reautoorization' => array(
								'url' => 'Yii::app()->createUrl("/platformaccount/platformaliexpressaccount/reauthorization", array("id" => $data->id))',
								'label' => '重新授权',
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformaliexpressaccount-grid',
									'width'     => '',
									'height'    => '360',
								),
								'visible' => '$data->is_visible == 1'
						),
						'setapi' => array(
								'url' => 'Yii::app()->createUrl("/platformaccount/platformaliexpressaccount/setapi", array("id" => $data->id))',
								'label' => '授权API设置',
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformaliexpressaccount-grid',
									'width'     => '',
									'height'    => '',
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