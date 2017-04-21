<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformebayaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(	
			array(
					'text'          => Yii::t('system', 'Add New Account'),
					'url'           => '/platformaccount/platformebayaccount/add',
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'platformebayaccount-grid',
							'postType'  => '',
							'callback'  => '',
							'height'   => '280',
							'width'    => '620'
					)
			),
			array(
					'text' => Yii::t('system', 'Detection Token'),
					'url'  => '/platformaccount/platformebayaccount/verificationtoken',
					'htmlOptions' => array (
						'class'    => 'edit',
						'target'   => 'selectedToDo',
						'rel'      => 'platformebayaccount-grid',
						'postType' => 'string',
						'callback' => '',
						'height'   => '',
						'width'    => ''
					)
			),
			array(
				'text' => '同步到ebay账号表',
				'url'  => '/platformaccount/platformebayaccount/tooms',
				'htmlOptions' => array (
					'class'    => 'edit',
					'target'   => 'selectedToDo',
					'rel'      => 'platformebayaccount-grid',
					'title'	   => '确定同步吗?',
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
					'name' => 'store_site',
					'value' => 'EbaySite::getSiteName($data->store_site)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'status',
					'value' => 'PlatformEbayAccount::getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'token_status',
					'value' => 'PlatformEbayAccount::getStatus($data->token_status)',
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
					'value' => 'PlatformEbayAccount::getOmsStatus($data->to_oms_status)',
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
					'template' => '{updates}&nbsp;&nbsp;&nbsp;&nbsp;{reautoorization}',
					'buttons' => array(
						'updates' => array(
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformebayaccount/edit", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformebayaccount-grid',
									'width'     => '',
									'height'    => '',
								),
						),
						'reautoorization' => array(
								'url' => 'Yii::app()->createUrl("/platformaccount/platformebayaccount/reauthorization", array("id" => $data->id))',
								'label' => '重新授权',
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformebayaccount-grid',
									'width'     => '',
									'height'    => '',
								),
								'visible' => '$data->is_visible == 1'
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