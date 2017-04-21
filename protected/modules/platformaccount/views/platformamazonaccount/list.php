<?php
Yii::app()->clientscript->scriptMap['jquery.js'] = false;
$row = 0;
$this->widget('UGridView', array(
	'id' => 'platformamazonaccount-grid',
	'dataProvider' => $model->search(),
	'filter' => $model,
	'toolBar' => array(	
			array(
					'text'          => Yii::t('system', 'Add New Account'),
					'url'           => '/platformaccount/platformamazonaccount/add',
					'htmlOptions'   => array(
							'class'     => 'add',
							'target'    => 'dialog',
							'rel'       => 'platformamazonaccount-grid',
							'postType'  => '',
							'callback'  => '',
							'height'   => '300',
							'width'    => '620'
					)
			),
			array(
				'text' => '同步到amazon账号表',
				'url'  => '/platformaccount/platformamazonaccount/tooms',
				'htmlOptions' => array (
					'class'    => 'edit',
					'target'   => 'selectedToDo',
					'rel'      => 'platformamazonaccount-grid',
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
					'name' => 'account_name',
					'value' => '$data->account_name',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'country_code',
					'value' => '$data->country_code',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'status',
					'value' => 'PlatformAmazonAccount::getStatus($data->status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'token_status',
					'value' => 'PlatformAmazonAccount::getStatus($data->token_status)',
					'htmlOptions' => array('style' => 'width:120px;'),
			),
			array(
					'name' => 'update_time',
					'value' => '$data->update_time',
					'htmlOptions' => array('style' => 'width:150px;'),
			),
			array(
					'name' => 'to_oms_status',
					'value' => 'PlatformAmazonAccount::getOmsStatus($data->to_oms_status)',
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
								'url'   => 'Yii::app()->createUrl("/platformaccount/platformamazonaccount/edit", array("id" => $data->id))',
								'label' => Yii::t('system', 'Edit'),
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformamazonaccount-grid',
									'width'     => '',
									'height'    => '',
								),
						),
						'reautoorization' => array(
								'url' => 'Yii::app()->createUrl("/platformaccount/platformamazonaccount/reauthorization", array("id" => $data->id))',
								'label' => '重新授权',
								'headerHtmlOptions' => array('height' => '20', 'align' => 'center'),
								'options' => array(
									'target'    => 'dialog',
									'mask'		=>true,
									'rel' 		=> 'platformamazonaccount-grid',
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